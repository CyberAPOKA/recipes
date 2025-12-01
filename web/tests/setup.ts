import { expect, afterEach } from 'vitest'
import * as matchers from '@testing-library/jest-dom/matchers'

// Extend Vitest's expect with jest-dom matchers
expect.extend(matchers)

// Cleanup after each test (Vitest handles cleanup automatically)
afterEach(() => {
  // Vitest automatically cleans up DOM between tests
})

