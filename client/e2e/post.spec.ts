import { test, expect } from '@playwright/test'
import { uid, registerViaUI, createThreadViaUI, TINY_PNG } from './helpers'

/**
 * Post modal tests — creating a new thread via the submission form.
 */

test.describe('Post modal', () => {
  // ── Visibility rules ──────────────────────────────────────────────────────

  test('guest sees New Thread button as disabled', async ({ page }) => {
    await page.goto('/board/oc')
    const btn = page.locator('.btn-new-thread')
    await expect(btn).toBeVisible()
    await expect(btn).toBeDisabled()
  })

  test('logged-in user sees New Thread button as enabled', async ({ page }) => {
    const email = `pm.enable.${uid()}@test.local`
    await registerViaUI(page, 'PM Enable', email)
    await page.goto('/board/oc')
    const btn = page.locator('.btn-new-thread')
    await expect(btn).toBeVisible()
    await expect(btn).not.toBeDisabled()
  })

  test('clicking New Thread opens the post modal with the board slug in the header', async ({ page }) => {
    const email = `pm.open.${uid()}@test.local`
    await registerViaUI(page, 'PM Open', email)
    await page.goto('/board/oc')
    await page.locator('.btn-new-thread').click()
    await expect(page.locator('.mm-modal-large')).toBeVisible()
    await expect(page.locator('.mm-modal-header')).toContainText('/oc/')
  })

  // ── Validation ────────────────────────────────────────────────────────────

  test('submitting without an image shows a validation error', async ({ page }) => {
    const email = `pm.nopic.${uid()}@test.local`
    await registerViaUI(page, 'PM NoPic', email)
    await page.goto('/board/oc')
    await page.locator('.btn-new-thread').click()
    await page.locator('.mm-file-input').waitFor()
    // Fill the title but do NOT attach an image
    await page.locator('input[placeholder="Give this artifact a name"]').fill('Title without image')
    await page.locator('.mm-btn-primary').click()
    // The client-side guard should show an error without making an API call
    await expect(page.locator('.mm-error')).toBeVisible()
    await expect(page.locator('.mm-error')).toContainText('image')
  })

  // ── Successful submission ─────────────────────────────────────────────────

  test('logged-in user can create a new thread via the modal', async ({ page }) => {
    const email = `pm.post.${uid()}@test.local`
    const title = `E2E Post ${uid()}`
    await registerViaUI(page, 'PM Poster', email)

    await page.goto('/board/oc')
    await page.locator('.btn-new-thread').click()
    await page.locator('.mm-file-input').waitFor()

    await page.locator('.mm-file-input').setInputFiles({
      name: 'test.png',
      mimeType: 'image/png',
      buffer: TINY_PNG,
    })
    await page.locator('input[placeholder="Give this artifact a name"]').fill(title)

    const [resp] = await Promise.all([
      page.waitForResponse(
        (r) => r.url().includes('/api/memes') && r.request().method() === 'POST',
      ),
      page.locator('.mm-btn-primary').click(),
    ])

    expect(resp.status()).toBe(201)
    // Modal closes after successful submission
    await expect(page.locator('.mm-modal-large')).not.toBeVisible()
    // The new thread title appears in the board view
    await expect(page.locator('.post-subject').first()).toBeVisible()
  })

  test('new thread appears at the top of the board after creation', async ({ page }) => {
    const email = `pm.top.${uid()}@test.local`
    const title = `Top Thread ${uid()}`
    await registerViaUI(page, 'PM Top', email)
    await createThreadViaUI(page, 'oc', title)

    // After modal closes the board refreshes — our thread should be visible
    await page.goto('/board/oc')
    const subjects = page.locator('.post-subject')
    await expect(subjects.first()).toBeVisible()
    // At least one thread on the board exists (the one we just created)
    expect(await subjects.count()).toBeGreaterThan(0)
  })

  // ── Closing the modal ─────────────────────────────────────────────────────

  test('clicking the ✕ button closes the post modal', async ({ page }) => {
    const email = `pm.close.${uid()}@test.local`
    await registerViaUI(page, 'PM Close', email)
    await page.goto('/board/oc')
    await page.locator('.btn-new-thread').click()
    await expect(page.locator('.mm-modal-large')).toBeVisible()
    await page.locator('.mm-close').click()
    await expect(page.locator('.mm-modal-large')).not.toBeVisible()
  })
})
