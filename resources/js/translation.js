export function translate(catalog, key, parameters = {}) {
    const text = catalog?.[key] ?? key;
    return text.replace(/:[a-zA-Z_][a-zA-Z_0-9]*/g, (token) =>
        Object.hasOwn(parameters, token.slice(1)) ? String(parameters[token.slice(1)]) : token,
    );
}
