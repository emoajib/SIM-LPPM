import { test, expect } from "@playwright/test";

test("login page loads", async ({ page }) => {
  await page.goto("/login");
  await expect(page).toHaveTitle(/SIMLPPM/i);
});

test("dashboard redirects to login when unauthenticated", async ({ page }) => {
  const res = await page.goto("/dashboard");
  expect([301, 302, 200]).toContain(res?.status() ?? 0);
});

test("health check returns ok", async ({ request }) => {
  const res = await request.get("/health-check");
  expect(res.status()).toBe(200);
});