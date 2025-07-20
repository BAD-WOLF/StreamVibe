export function validateLoginFields(email: string, password: string): Record<string, string> {
    const errors: Record<string, string> = {};

    if (!email.trim()) errors.email = "Email é obrigatório.";
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) errors.email = "Formato de email inválido.";

    if (!password) errors.password = "Senha é obrigatória.";
    else if (password.length < 8) errors.password = "Senha deve ter ao menos 8 caracteres.";

    return errors;
}
