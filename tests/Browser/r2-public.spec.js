import { expect, test } from '@playwright/test';
import { mkdirSync } from 'node:fs';
import { join } from 'node:path';

test.describe('R2 public header and D2 hero', () => {
  test('desktop communicates the product and exposes valid navigation', async ({ page }) => {
    await page.setViewportSize({ width: 1440, height: 900 });
    await page.goto('/');

    await expect(page.getByRole('heading', {
      level: 1,
      name: 'Treine decisões. Avalie a execução. Transforme cada cenário em aprendizado.',
    })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Conhecer a plataforma' })).toHaveAttribute('href', '#recursos');
    await expect(page.getByRole('link', { name: 'Acessar o ambiente' }).first()).toHaveAttribute('href', /\/login$/);
    await expect(page.getByText('Dados ilustrativos')).toBeVisible();
  });

  test('mobile menu is keyboard-operable and the page does not overflow', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto('/');

    const trigger = page.locator('button[aria-controls="public-navigation"]');
    await expect(trigger).toHaveAttribute('aria-label', 'Abrir menu de navegação');
    await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    await trigger.focus();
    await page.keyboard.press('Enter');
    await expect(trigger).toHaveAttribute('aria-expanded', 'true');
    await expect(trigger).toHaveAttribute('aria-label', 'Fechar menu de navegação');
    await expect(page.locator('#public-navigation')).toBeVisible();

    await page.keyboard.press('Escape');
    await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    await expect(trigger).toBeFocused();
    await expect(page.locator('#public-navigation')).toBeHidden();

    const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
    expect(overflow).toBeLessThanOrEqual(1);
  });

  test('reduced motion removes authored transitions and smooth scrolling', async ({ page }) => {
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await page.goto('/');

    const motion = await page.evaluate(() => {
      const root = getComputedStyle(document.documentElement);
      const hero = getComputedStyle(document.querySelector('[data-d2-hero]'));

      return {
        scrollBehavior: root.scrollBehavior,
        transitionDuration: hero.transitionDuration,
        animationDuration: hero.animationDuration,
      };
    });

    expect(motion.scrollBehavior).toBe('auto');
    expect(Number.parseFloat(motion.transitionDuration)).toBeLessThanOrEqual(0.01);
    expect(Number.parseFloat(motion.animationDuration)).toBeLessThanOrEqual(0.01);
  });

  test('target viewports have clean runtime, valid assets, and no horizontal overflow', async ({ browser }) => {
    const viewports = [
      { width: 390, height: 844, name: 'mobile-390x844' },
      { width: 768, height: 1024, name: 'tablet-768x1024' },
      { width: 1440, height: 900, name: 'desktop-1440x900' },
      { width: 1920, height: 1080, name: 'desktop-1920x1080' },
    ];

    const screenshotDirectory = process.env.R2_SCREENSHOT_DIR;
    if (screenshotDirectory) mkdirSync(screenshotDirectory, { recursive: true });

    for (const viewport of viewports) {
      const page = await browser.newPage({ viewport });
      const consoleErrors = [];
      const pageErrors = [];
      const requestFailures = [];
      const mixedContent = [];
      const assetErrors = [];

      page.on('console', (message) => {
        if (message.type() === 'error') consoleErrors.push(message.text());
        if (/mixed content/i.test(message.text())) mixedContent.push(message.text());
      });
      page.on('pageerror', (error) => pageErrors.push(error.message));
      page.on('requestfailed', (request) => requestFailures.push(`${request.url()} — ${request.failure()?.errorText ?? 'unknown'}`));
      page.on('response', (response) => {
        const url = response.url();
        if (!url.includes('/build/assets/')) return;

        const contentType = response.headers()['content-type'] ?? '';
        const validMime = url.endsWith('.css')
          ? contentType.includes('text/css')
          : !url.endsWith('.js') || /javascript|ecmascript/.test(contentType);

        if (!response.ok() || !validMime) {
          assetErrors.push(`${response.status()} ${contentType} ${url}`);
        }
      });

      await page.goto('/');
      await page.waitForLoadState('networkidle');

      const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
      expect(overflow, `${viewport.name} horizontal overflow`).toBeLessThanOrEqual(1);
      await expect(page.locator('[data-d2-hero]')).toBeVisible();
      await expect(page.locator('.d2-product-card')).toBeVisible();
      expect(consoleErrors, `${viewport.name} console errors`).toEqual([]);
      expect(pageErrors, `${viewport.name} page errors`).toEqual([]);
      expect(requestFailures, `${viewport.name} request failures`).toEqual([]);
      expect(mixedContent, `${viewport.name} mixed content`).toEqual([]);
      expect(assetErrors, `${viewport.name} asset errors`).toEqual([]);

      if (screenshotDirectory) {
        await page.screenshot({
          path: join(screenshotDirectory, `${viewport.name}.png`),
          fullPage: true,
        });
      }

      await page.close();
    }
  });
});
