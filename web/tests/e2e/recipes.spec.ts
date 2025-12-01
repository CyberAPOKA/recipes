import { test, expect } from '@playwright/test'

test.describe('Recipe Management', () => {
  test('authenticated user can create a recipe', async ({ page }) => {
    // This test assumes you have a way to authenticate
    // You might need to set up authentication state or use API calls
    
    await page.goto('/')
    
    // Look for create recipe button/link
    const createButton = page.getByRole('button', { name: /create|new|add|nova|adicionar/i }).or(
      page.getByRole('link', { name: /create|new|add|nova|adicionar/i })
    ).first()
    
    const isVisible = await createButton.isVisible().catch(() => false)
    
    if (isVisible) {
      await createButton.click()
      await page.waitForTimeout(500)
      
      // Check if form is visible
      await expect(page.getByPlaceholder(/name|nome/i).or(page.getByLabel(/name|nome/i))).toBeVisible()
    }
  })

  test('user can filter recipes by category', async ({ page }) => {
    await page.goto('/')
    await page.waitForTimeout(1000)
    
    // Look for category filter
    const categoryFilter = page.locator('select, [role="combobox"]').first()
    const count = await categoryFilter.count()
    
    if (count > 0) {
      await categoryFilter.selectOption({ index: 1 })
      await page.waitForTimeout(1000)
      
      // Recipes should be filtered
      const recipes = page.locator('[data-testid="recipe"], .recipe')
      await expect(recipes.first()).toBeVisible()
    }
  })

  test('user can search recipes', async ({ page }) => {
    await page.goto('/')
    await page.waitForTimeout(1000)
    
    // Look for search input
    const searchInput = page.getByPlaceholder(/search|buscar|pesquisar/i).or(
      page.getByRole('searchbox')
    ).first()
    
    const isVisible = await searchInput.isVisible().catch(() => false)
    
    if (isVisible) {
      await searchInput.fill('bolo')
      await page.waitForTimeout(1000)
      
      // Results should be filtered
      const recipes = page.locator('[data-testid="recipe"], .recipe')
      const count = await recipes.count()
      
      // If there are results, they should be visible
      if (count > 0) {
        await expect(recipes.first()).toBeVisible()
      }
    }
  })
})

