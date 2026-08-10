import { expect, test } from '@playwright/test';

const manager = {
    email: 'demo.manager@example.test',
    password: 'Demo-M5-2026!',
};

test.beforeEach(async ({ page }) => {
    page.on('console', (message) => {
        if (message.type() === 'error') {
            console.log(`[browser-console:error] ${message.text()}`);
        }
    });

    page.on('pageerror', (error) => {
        console.log(`[browser-pageerror] ${error.stack ?? error.message}`);
    });
});

async function waitForAlpine(page) {
    await page.waitForFunction(() => {
        const topbar = document.querySelector('header[x-data]');
        const themeButton = document.querySelector('[data-theme-toggle]');
        const dropdown = document.querySelector('[x-data="{ open: false }"]');

        return Boolean(
            window.Alpine
            && typeof window.Alpine.store === 'function'
            && window.Alpine.store('theme')
            && topbar?._x_dataStack
            && themeButton?._x_attributeCleanups
            && dropdown?._x_dataStack,
        );
    });
}

async function login(page) {
    await page.goto('/login');

    await expect(page.getByRole('heading', { name: 'Entrar no sistema' })).toBeVisible();

    const email = page.locator('input[name="email"]');
    await expect(email).toBeFocused();
    await email.fill(manager.email);
    await page.locator('input[name="password"]').fill(manager.password);
    await page.getByRole('button', { name: 'Entrar com segurança' }).click();

    await expect(page).toHaveURL(/\/dashboard(?:\?.*)?$/);
    await expect(page.getByRole('heading', { name: 'Painel do instrutor' })).toBeVisible();
    await waitForAlpine(page);
}

async function expectHealthyPage(page, path) {
    const response = await page.goto(path);

    expect(response, `No HTTP response for ${path}`).not.toBeNull();
    expect(response.status(), `Unexpected HTTP status for ${path}`).toBeLessThan(400);
    await expect(page.locator('main#main')).toBeVisible();
    await expect(page.getByText('Server Error', { exact: true })).toHaveCount(0);
}

test('manager can traverse the institutional application and operational workspaces', async ({ page }) => {
    await login(page);

    const skipLink = page.getByRole('link', { name: 'Pular para o conteúdo' });
    await page.keyboard.press('Tab');
    await expect(skipLink).toBeFocused();

    const themeToggle = page.getByRole('button', { name: 'Alternar modo de baixa luz' });
    await themeToggle.click();
    await expect(page.locator('html')).toHaveAttribute('data-theme', 'low-light');
    await themeToggle.click();
    await expect(page.locator('html')).toHaveAttribute('data-theme', 'light');

    for (const path of [
        '/dashboard',
        '/dashboard/executive',
        '/scenarios',
        '/scenario-templates',
        '/history/executions',
        '/knowledge',
        '/people',
        '/organizations',
        '/access',
    ]) {
        await expectHealthyPage(page, path);
    }

    await page.goto('/dashboard');
    const executionLink = page.locator('a[href*="/executions/"]').first();
    await expect(executionLink).toBeVisible();
    await executionLink.click();
    await expect(page).toHaveURL(/\/executions\//);
    await expect(page.locator('main#main')).toBeVisible();

    await page.goto('/dashboard');
    const assessmentLink = page.locator('a[href*="/assessments/"]').first();
    await expect(assessmentLink).toBeVisible();
    await assessmentLink.click();
    await expect(page).toHaveURL(/\/assessments\//);
    await expect(page.locator('main#main')).toBeVisible();
});

test('logout invalidates the browser session', async ({ page }) => {
    await login(page);

    await page.getByRole('button', { name: 'Abrir menu da conta' }).click();

    const logout = page.getByRole('menuitem', { name: 'Encerrar sessão' });
    await expect(logout).toBeVisible();
    await logout.click();

    await expect(page).toHaveURL(/\/login$/);

    await page.goto('/dashboard');
    await expect(page).toHaveURL(/\/login$/);
    await expect(page.getByRole('heading', { name: 'Entrar no sistema' })).toBeVisible();
});
