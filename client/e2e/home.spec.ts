import { test, expect } from '@playwright/test'

test.describe('Homepage', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/')
  })

  test('shows the Galleries & Boards section', async ({ page }) => {
    await expect(page.locator('.section-title')).toContainText('Galleries & Boards')
  })

  test('lists at least one board row', async ({ page }) => {
    const rows = page.locator('.board-row')
    await expect(rows.first()).toBeVisible()
    expect(await rows.count()).toBeGreaterThan(0)
  })

  test('sidebar contains the tag cloud block', async ({ page }) => {
    await expect(page.locator('.sidebar')).toBeVisible()
    await expect(page.locator('.tag-cloud')).toBeVisible()
  })
})
