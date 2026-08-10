import { expect, test } from '@playwright/test';

const manager = {
    email: 'demo.manager@example.test',
    password: 'Demo-M5-2026!',
};

test.beforeEach(async ({ page }) => {
    page.on('console', (message) => {
        if (['error', 'warning'].includes(message.type())) {
            console.log(`[browser-console:${message.type()}] ${message.text()}`);
        }
    });

    page.on('pageerror', (error) => {
        console.log(`[browser-pageerror] ${error.stack ?? error.message}`);
    });
});

async function waitForAlpine(page) {
    await page.waitForFunction(() => Boolean(
        window.Alpine
        && typeof window.Alpine.store === 'function'
        && window.Alpine.store('theme'),
    ));

    const state = await page.evaluate(async () => {
        const themeButton = document.querySelector('[data-theme-toggle]');
        const dropdown = document.querySelector('[x-data="{ open: false }"]');
        const accountButton = document.querySelector('[aria-label="Abrir menu da conta"]');
        const dropdownTrigger = accountButton?.parentElement;
        const store = window.Alpine.store('theme');

        const before = {
            bodyInitialized: Boolean(document.body?._x_dataStack),
            alpineVersion: window.Alpine?.version ?? null,
            theme: store?.current ?? null,
            themeHandler: themeButton?.getAttribute('x-on:click') ?? null,
            themeButtonInitialized: Boolean(themeButton?._x_attributeCleanups),
            dropdownInitialized: Boolean(dropdown?._x_dataStack),
            dropdownTriggerHandler: dropdownTrigger?.getAttribute('x-on:click') ?? null,
            dropdownTriggerInitialized: Boolean(dropdownTrigger?._x_attributeCleanups),
            dropdownOpen: dropdown?._x_dataStack?.[0]?.open ?? null,
        };

        themeButton?.click();
        await new Promise((resolve) => setTimeout(resolve, 50));
        const themeAfterNativeClick = store?.current ?? null;

        store?.toggle();
        await new Promise((resolve) => setTimeout(resolve, 50));
        const themeAfterDirectStoreToggle = store?.current ?? null;

        if (store) {
            store.current = 'light';
            store.apply();
            try { localStorage.setItem('tsl-theme', 'light'); } catch (_) {}
        }

        dropdownTrigger?.click();
        await new Promise((resolve) => setTimeout(resolve, 50));
        const dropdownAfterNativeClick = dropdown?._x_dataStack?.[0]?.open ?? null;
        if (dropdown?._x_dataStack?.[0]) {
            dropdown._x_dataStack[0].open = false;
        }

        return {
            ...before,
            themeAfterNativeClick,
            themeAfterDirectStoreToggle,
            dropdownAfterNativeClick,
        };
    });

    console.log(`[alpine-binding-probe] ${JSON.stringify(state)}`);
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
    await page.getByRole('button', { name: 'Encerrar sessão' }).click();

    await expect(page).toHaveURL(/\/login$/);

    await page.goto('/dashboard');
    await expect(page).toHaveURL(/\/login$/);
    await expect(page.getByRole('heading', { name: 'Entrar no sistema' })).toBeVisible();
});
