import { test, expect } from '@playwright/test'

test.describe('Authentication', () => {
  test('clicking Sign In opens the auth modal', async ({ page }) => {
    await page.goto('/')
    await page.locator('.nav-login').click()
    await expect(page.locator('.mm-modal')).toBeVisible()
    await expect(page.locator('.mm-tabs')).toBeVisible()
  })

  test('a new user can register and is shown as logged in', async ({ page }) => {
    const email = `e2e.${Date.now()}@test.local`

    await page.goto('/')
    await page.locator('.nav-login').click()
    await expect(page.locator('.mm-modal')).toBeVisible()

    // Switch to the Register tab
    await page.locator('.mm-tab').nth(1).click()

    // Fill the register form by input type and position
    await page.locator('.mm-body input[type="text"]').fill('E2E Tester')
    await page.locator('.mm-body input[type="email"]').fill(email)
    const pwdFields = page.locator('.mm-body input[type="password"]')
    await pwdFields.nth(0).fill('password123')
    await pwdFields.nth(1).fill('password123')

    // Submit and verify the API call succeeded before checking UI state
    const [response] = await Promise.all([
      page.waitForResponse(r => r.url().includes('/api/register')),
      page.locator('.mm-btn-primary').click(),
    ])
    expect(response.status()).toBe(200)

    await expect(page.locator('.mm-modal')).not.toBeVisible()
    await expect(page.locator('.nav-curator')).toBeVisible()
    await expect(page.locator('.nav-curator')).toContainText('E2E Tester')
  })

  test('a logged-in user can sign out and returns to guest state', async ({ page }) => {
    const email = `e2e.lo.${Date.now()}@test.local`

    // Register and log in entirely through the UI (self-contained)
    await page.goto('/')
    await page.locator('.nav-login').click()
    await expect(page.locator('.mm-modal')).toBeVisible()
    await page.locator('.mm-tab').nth(1).click()
    await page.locator('.mm-body input[type="text"]').fill('Logout Tester')
    await page.locator('.mm-body input[type="email"]').fill(email)
    const pwdFields = page.locator('.mm-body input[type="password"]')
    await pwdFields.nth(0).fill('password123')
    await pwdFields.nth(1).fill('password123')
    const [reg] = await Promise.all([
      page.waitForResponse(r => r.url().includes('/api/register')),
      page.locator('.mm-btn-primary').click(),
    ])
    expect(reg.status()).toBe(200)
    await expect(page.locator('.nav-curator')).toBeVisible()

    // Sign out — wait for the logout API call to complete before asserting
    const [logoutResp] = await Promise.all([
      page.waitForResponse(r => r.url().includes('/api/logout')),
      page.locator('.nav-login').click(),
    ])
    expect(logoutResp.status()).toBe(200)
    await expect(page.locator('.nav-login')).toContainText('Sign In')
    await expect(page.locator('.nav-curator')).not.toBeVisible()
  })
})
