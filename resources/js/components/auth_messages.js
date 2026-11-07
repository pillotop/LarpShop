// Blade supplies the copy for every state of the shared authentication form.
export const authMessages = JSON.parse(
    document.querySelector('[data-auth-messages]')?.textContent ?? '{}',
);

export const authStateDefaults = Object.fromEntries(
    Object.entries(authMessages.states ?? {}).map(([name, state]) => [
        name,
        {
            header: state.header,
            loading: () => state.loading,
        },
    ]),
);
