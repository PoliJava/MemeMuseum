import { test, expect } from '@playwright/test'

test.describe('Board browsing', () => {
  test('clicking a board row navigates to its board page', async ({ page }) => {
    await page.goto('/')
    await expect(page.locator('.board-row').first()).toBeVisible()
    await page.locator('.board-row').first().click()
    await expect(page).toHaveURL(/\/board\//)
  })

  test('board page displays the board slug in the header', async ({ page }) => {
    await page.goto('/board/oc')
    await expect(page.locator('.board-header-slug')).toContainText('/oc/')
  })

  test('board with a sticky post shows the pinned notice', async ({ page }) => {
    await page.goto('/board/oc')
    await expect(page.locator('.sticky-post')).toBeVisible()
    await expect(page.locator('.sticky-pin')).toContainText('[STICKY]')
  })
})
