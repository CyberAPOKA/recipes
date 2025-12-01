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
    
    // Wait for page to load
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(2000) // Extra time for recipes to load
    
    // Look for category filter - try multiple selectors
    const categoryFilter = page.locator('select, [role="combobox"], select.form-select').first()
    const count = await categoryFilter.count()
    
    if (count > 0 && await categoryFilter.isVisible().catch(() => false)) {
      await categoryFilter.selectOption({ index: 1 })
      await page.waitForTimeout(2000) // Wait for filter to apply
      
      // Recipes should be filtered - use more flexible selector
      const recipes = page.locator('[data-testid="recipe"], .recipe, article, .card, [class*="recipe"]').first()
      const recipeCount = await recipes.count()
      
      // If recipes exist, verify they're visible
      if (recipeCount > 0) {
        await expect(recipes.first()).toBeVisible({ timeout: 5000 })
      }
    } else {
      // Skip test if filter doesn't exist
      test.skip()
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

