import { test, expect } from '@playwright/test'
import { uid, registerViaUI, createThreadViaUI } from './helpers'

/**
 * Reply interaction tests.
 *
 * All tests in this suite need an authenticated user and a thread to reply to.
 * beforeAll creates both once; individual tests restore the session via
 * storageState so they run as that same user without re-logging in.
 */

let threadId: number
let savedState: { cookies: any[]; origins: any[] }

test.describe.serial('Reply interactions', () => {
  test.beforeAll(async ({ browser }) => {
    const ctx = await browser.newContext()
    const page = await ctx.newPage()

    const email = `reply.${uid()}@test.local`
    await registerViaUI(page, 'Reply Tester', email)
    threadId = await createThreadViaUI(page, 'oc', `Reply Test Thread ${uid()}`)

    savedState = await ctx.storageState()
    await ctx.close()
  })

  // ── Opening the reply modal ───────────────────────────────────────────────

  test('logged-in user sees the [Reply] button in the thread view', async ({ browser }) => {
    const ctx = await browser.newContext({ storageState: savedState })
    const page = await ctx.newPage()

    await page.goto(`/thread/${threadId}`)
    // The inline [Reply] button is v-if="user" — should appear when authenticated
    await expect(page.locator('.op-post .inline-btn').filter({ hasText: '[Reply]' })).toBeVisible()

    await ctx.close()
  })

  test('clicking [Reply] opens the reply modal', async ({ browser }) => {
    const ctx = await browser.newContext({ storageState: savedState })
    const page = await ctx.newPage()

    await page.goto(`/thread/${threadId}`)
    await page.locator('.op-post .inline-btn').filter({ hasText: '[Reply]' }).click()

    await expect(page.locator('.mm-overlay')).toBeVisible()
    await expect(page.locator('.mm-modal')).toBeVisible()

    await ctx.close()
  })

  // ── Posting a reply ───────────────────────────────────────────────────────

  test('logged-in user can post a text reply', async ({ browser }) => {
    const ctx = await browser.newContext({ storageState: savedState })
    const page = await ctx.newPage()

    await page.goto(`/thread/${threadId}`)
    await page.locator('.op-post .inline-btn').filter({ hasText: '[Reply]' }).click()
    await page.locator('.mm-modal textarea').fill('This is an automated e2e reply.')

    const [resp] = await Promise.all([
      page.waitForResponse((r) => r.url().includes('/api/comments')),
      page.locator('.mm-btn-primary').click(),
    ])

    expect(resp.status()).toBe(201)
    // Modal should close automatically after a successful post
    await expect(page.locator('.mm-overlay')).not.toBeVisible()
    // New reply appears at the bottom of the thread
    await expect(page.locator('.replies-section')).toBeVisible()
    await expect(page.locator('.reply-post').last()).toContainText('This is an automated e2e reply.')

    await ctx.close()
  })

  // ── No.X quoting ─────────────────────────────────────────────────────────

  test('clicking No.X on the OP opens the reply modal pre-filled with >>id', async ({ browser }) => {
    const ctx = await browser.newContext({ storageState: savedState })
    const page = await ctx.newPage()

    await page.goto(`/thread/${threadId}`)
    // Click the clickable post number on the OP
    await page.locator('.post-no--link').first().click()

    await expect(page.locator('.mm-overlay')).toBeVisible()
    // Textarea should be pre-filled with the quote token
    const body = await page.locator('.mm-modal textarea').inputValue()
    expect(body).toContain(`>>${threadId}`)

    await ctx.close()
  })

  // ── Editing a reply ───────────────────────────────────────────────────────

  test('logged-in user can edit their own reply', async ({ browser }) => {
    const ctx = await browser.newContext({ storageState: savedState })
    const page = await ctx.newPage()

    await page.goto(`/thread/${threadId}`)

    // Post a reply that we will immediately edit
    await page.locator('.op-post .inline-btn').filter({ hasText: '[Reply]' }).click()
    await page.locator('.mm-modal textarea').fill('Original text before edit.')
    await Promise.all([
      page.waitForResponse((r) => r.url().includes('/api/comments')),
      page.locator('.mm-btn-primary').click(),
    ])
    await expect(page.locator('.mm-overlay')).not.toBeVisible()

    // Click [Edit] on the reply that was just added
    await page.locator('.post-own-actions .inline-btn').filter({ hasText: '[Edit]' }).last().click()

    // An inline textarea replaces the body
    const editArea = page.locator('.edit-textarea')
    await expect(editArea).toBeVisible()
    await editArea.fill('Edited reply text.')

    const [editResp] = await Promise.all([
      page.waitForResponse(
        (r) => r.url().includes('/api/comments') && r.request().method() === 'PUT',
      ),
      page.locator('.inline-btn').filter({ hasText: '[Save]' }).click(),
    ])

    expect(editResp.status()).toBe(200)
    // Edit area gone; updated text visible
    await expect(editArea).not.toBeVisible()
    await expect(page.locator('.reply-post').last()).toContainText('Edited reply text.')

    await ctx.close()
  })

  // ── Deleting a reply ──────────────────────────────────────────────────────

  test('logged-in user can delete their own reply', async ({ browser }) => {
    const ctx = await browser.newContext({ storageState: savedState })
    const page = await ctx.newPage()

    await page.goto(`/thread/${threadId}`)

    // Post a reply we will immediately delete
    await page.locator('.op-post .inline-btn').filter({ hasText: '[Reply]' }).click()
    await page.locator('.mm-modal textarea').fill('Reply scheduled for deletion.')
    await Promise.all([
      page.waitForResponse((r) => r.url().includes('/api/comments')),
      page.locator('.mm-btn-primary').click(),
    ])
    await expect(page.locator('.mm-overlay')).not.toBeVisible()

    const countBefore = await page.locator('.reply-post').count()

    // Auto-accept the confirm() dialog before triggering delete
    page.once('dialog', (d) => d.accept())
    await page.locator('.post-own-actions .inline-btn--danger').last().click()

    // Wait for one fewer reply-post in the DOM
    await expect(page.locator('.reply-post')).toHaveCount(countBefore - 1)

    await ctx.close()
  })

  // ── Cancel edit ───────────────────────────────────────────────────────────

  test('[Cancel] in edit mode restores the original reply body', async ({ browser }) => {
    const ctx = await browser.newContext({ storageState: savedState })
    const page = await ctx.newPage()

    await page.goto(`/thread/${threadId}`)

    // Post a reply
    await page.locator('.op-post .inline-btn').filter({ hasText: '[Reply]' }).click()
    await page.locator('.mm-modal textarea').fill('Text that must survive cancellation.')
    await Promise.all([
      page.waitForResponse((r) => r.url().includes('/api/comments')),
      page.locator('.mm-btn-primary').click(),
    ])
    await expect(page.locator('.mm-overlay')).not.toBeVisible()

    // Open edit mode
    await page.locator('.post-own-actions .inline-btn').filter({ hasText: '[Edit]' }).last().click()
    await page.locator('.edit-textarea').fill('Discarded change.')

    // Cancel — no API call should fire
    await page.locator('.inline-btn').filter({ hasText: '[Cancel]' }).click()

    // Edit area gone; original text still present
    await expect(page.locator('.edit-textarea')).not.toBeVisible()
    await expect(page.locator('.reply-post').last()).toContainText('Text that must survive cancellation.')

    await ctx.close()
  })
})
