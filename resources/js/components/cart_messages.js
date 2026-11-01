// Laravel supplies the messages; frontend modules only substitute named values.
const messages = JSON.parse(document.getElementById('cart-messages')?.textContent ?? '{}');

export function cartMessage(key, values = {}) {
    let message = messages[key] ?? key;
    for (const [name, value] of Object.entries(values)) {
        message = message.replaceAll(`:${name}`, String(value));
    }
    return message;
}
