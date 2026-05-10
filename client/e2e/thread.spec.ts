import { test, expect } from '@playwright/test'
import { uid, registerViaUI, createThreadViaUI } from './helpers'

/**
 * Thread view tests.
 *
 * beforeAll registers a test user and creates one thread via the PostModal so
 * every test in this block has a deterministic thread to navigate to.
 * The tests themselves are read-only (no auth required) — they verify the
 * thread view renders correctly from the perspective of a guest visitor.
 */

let threadId: number
let threadTitle: string

test.describe.serial('Thread view', () => {
  test.beforeAll(async ({ browser }) => {
    const ctx = await browser.newContext()
    const page = await ctx.newPage()

    const email = `tv.${uid()}@test.local`
    threadTitle = `E2E Thread ${uid()}`

    await registerViaUI(page, 'Thread Viewer', email)
    threadId = await createThreadViaUI(page, 'oc', threadTitle)

    await ctx.close()
  })

  // ── Navigation ───────────────────────────────────────────────────────────────

  test('clicking [Reply] on a board thread navigates to /thread/:id', async ({ page }) => {
    await page.goto('/board/oc')
    const link = page.locator('.post-reply-link').first()
    await expect(link).toBeVisible()
    await link.click()
    await expect(page).toHaveURL(/\/thread\/\d+/)
  })

  test('[View Thread] link also navigates to the thread page', async ({ page }) => {
    await page.goto('/board/oc')
    await expect(page.locator('.view-thread-btn').first()).toBeVisible()
    await page.locator('.view-thread-btn').first().click()
    await expect(page).toHaveURL(/\/thread\/\d+/)
  })

  // ── OP post rendering ────────────────────────────────────────────────────────

  test('thread view shows the OP post block', async ({ page }) => {
    await page.goto(`/thread/${threadId}`)
    await expect(page.locator('.op-post')).toBeVisible()
  })

  test('thread view displays the correct thread title', async ({ page }) => {
    await page.goto(`/thread/${threadId}`)
    await expect(page.locator('.post-subject')).toContainText(threadTitle)
  })

  test('thread view shows the post number (No.X)', async ({ page }) => {
    await page.goto(`/thread/${threadId}`)
    await expect(page.locator('.post-no').first()).toContainText(`No.${threadId}`)
  })

  // ── Meta elements ────────────────────────────────────────────────────────────

  test('thread view shows rating stars', async ({ page }) => {
    await page.goto(`/thread/${threadId}`)
    await expect(page.locator('.rating-stars')).toBeVisible()
  })

  test('thread view shows the view count', async ({ page }) => {
    await page.goto(`/thread/${threadId}`)
    await expect(page.locator('.post-views')).toBeVisible()
  })

  // ── Auth-gated elements ───────────────────────────────────────────────────────

  test('guest does not see the [Reply] button inside thread view', async ({ page }) => {
    await page.goto(`/thread/${threadId}`)
    // The inline Reply button is v-if="user" — guests must not see it
    await expect(page.locator('.op-post .inline-btn')).not.toBeVisible()
  })
})
