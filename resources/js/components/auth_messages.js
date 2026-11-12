// Blade supplies the copy for every state of the shared authentication form.
export function loadAuthMessages() {
    return JSON.parse(
        document.querySelector('[data-auth-messages]')?.textContent ?? '{}',
    );
}

export function loadAuthStateDefaults(messages = loadAuthMessages()) {
    return Object.fromEntries(
        Object.entries(messages.states ?? {}).map(([name, state]) => [
            name,
            {
                header: state.header,
                loading: () => state.loading,
            },
        ]),
    );
}

export const authMessages = loadAuthMessages();
export const authStateDefaults = loadAuthStateDefaults(authMessages);
