import { test, expect } from "@playwright/test";

test("login page has skip link", async ({ page }) => {
  await page.goto("/login");
  await expect(page.locator(".skip-link")).toBeAttached();
});

test("login page has no horizontal scroll on mobile", async ({ page }) => {
  await page.setViewportSize({ width: 360, height: 800 });
  await page.goto("/login");
  const scrollWidth = await page.evaluate(() => document.documentElement.scrollWidth);
  const clientWidth = await page.evaluate(() => document.documentElement.clientWidth);
  expect(scrollWidth).toBeLessThanOrEqual(clientWidth + 1);
});
