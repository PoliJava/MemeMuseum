import { test, expect } from '@playwright/test'

test.describe('Search', () => {
  test('search page renders the filter form', async ({ page }) => {
    await page.goto('/search')
    await expect(page.locator('.search-filters')).toBeVisible()
    await expect(page.locator('select')).toBeVisible()
    await expect(page.locator('.btn-search')).toBeVisible()
  })

  test('Browse nav link leads to the search page', async ({ page }) => {
    await page.goto('/')
    await page.getByRole('link', { name: 'Browse' }).click()
    await expect(page).toHaveURL('/search')
  })

  test('submitting a tag filter reflects it in the URL', async ({ page }) => {
    await page.goto('/search')
    await page.locator('input[type="text"]').fill('Programming')
    await page.locator('.btn-search').click()
    await expect(page).toHaveURL(/tag=Programming/)
  })
})
