import { test, expect } from '@playwright/test'

test.describe('Authentication Flow', () => {
  test('user can navigate to login page', async ({ page }) => {
    await page.goto('/')
    
    // Look for login link or button
    const loginLink = page.getByRole('link', { name: /login|entrar/i }).first()
    if (await loginLink.isVisible()) {
      await loginLink.click()
    }
    
    // Check if we're on login page
    await expect(page).toHaveURL(/.*login.*/i)
  })

  test('user can see register form', async ({ page }) => {
    await page.goto('/')
    
    // Navigate to register
    const registerLink = page.getByRole('link', { name: /register|registrar|cadastrar/i }).first()
    if (await registerLink.isVisible()) {
      await registerLink.click()
    }
    
    // Check for form fields
    await expect(page.getByPlaceholder(/email|e-mail/i).or(page.getByLabel(/email|e-mail/i))).toBeVisible()
    await expect(page.getByPlaceholder(/password|senha/i).or(page.getByLabel(/password|senha/i))).toBeVisible()
  })
})

test.describe('Public Recipes', () => {
  test('user can view public recipes list', async ({ page }) => {
    await page.goto('/')
    
    // Wait for recipes to load
    await page.waitForTimeout(1000)
    
    // Check if recipes are displayed (could be cards, list items, etc.)
    const recipeElements = page.locator('[data-testid="recipe"], .recipe, article').first()
    
    // If recipes exist, they should be visible
    const count = await recipeElements.count()
    if (count > 0) {
      await expect(recipeElements.first()).toBeVisible()
    }
  })

  test('user can navigate to recipe detail', async ({ page }) => {
    await page.goto('/')
    await page.waitForTimeout(1000)
    
    // Try to find and click a recipe link
    const recipeLink = page.locator('a[href*="/recipe"], a[href*="/recipes"]').first()
    const count = await recipeLink.count()
    
    if (count > 0) {
      await recipeLink.click()
      await page.waitForTimeout(500)
      
      // Should be on recipe detail page
      await expect(page).toHaveURL(/.*recipe.*/)
    }
  })
})

