import type { Page } from '@playwright/test'

// ── Unique IDs ────────────────────────────────────────────────────────────────

export function uid(): string {
  return `${Date.now()}.${Math.random().toString(36).slice(2, 6)}`
}

// ── Tiny 1×1 transparent PNG ──────────────────────────────────────────────────
// Used as a minimal valid image file in upload tests.

export const TINY_PNG = Buffer.from(
  'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
  'base64',
)

// ── Auth helpers ──────────────────────────────────────────────────────────────

/**
 * Register a brand-new user via the UI (AuthModal → Register tab).
 * Assumes the page is at a route that shows the top nav.
 * Returns true if the /api/register call succeeded (HTTP 200).
 */
export async function registerViaUI(
  page: Page,
  name: string,
  email: string,
  password = 'password123',
): Promise<boolean> {
  await page.goto('/')
  await page.locator('.nav-login').click()
  await page.locator('.mm-tab').nth(1).click()  // "Register" tab

  await page.locator('.mm-body input[type="text"]').fill(name)
  await page.locator('.mm-body input[type="email"]').fill(email)
  const pwds = page.locator('.mm-body input[type="password"]')
  await pwds.nth(0).fill(password)
  await pwds.nth(1).fill(password)

  const [res] = await Promise.all([
    page.waitForResponse((r) => r.url().includes('/api/register')),
    page.locator('.mm-btn-primary').click(),
  ])
  return res.status() === 200
}

// ── Thread creation ───────────────────────────────────────────────────────────

/**
 * Open the PostModal on the given board, upload a tiny PNG, fill in the title,
 * and submit. Page must already be authenticated.
 *
 * Returns the numeric ID of the newly created thread.
 */
export async function createThreadViaUI(
  page: Page,
  boardSlug: string,
  title: string,
): Promise<number> {
  await page.goto(`/board/${boardSlug}`)
  await page.locator('.btn-new-thread').click()

  // Wait until the board resolver finishes and the file input is mounted
  await page.locator('.mm-file-input').waitFor()

  await page.locator('.mm-file-input').setInputFiles({
    name: 'test.png',
    mimeType: 'image/png',
    buffer: TINY_PNG,
  })

  // Use the placeholder attribute to target the title input unambiguously
  await page.locator('input[placeholder="Give this artifact a name"]').fill(title)

  const [resp] = await Promise.all([
    page.waitForResponse(
      (r) => r.url().includes('/api/memes') && r.request().method() === 'POST',
    ),
    page.locator('.mm-btn-primary').click(),
  ])

  const json = await resp.json()
  return (json as any).data.id as number
}
