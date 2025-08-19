import type {AuthMode} from "@auth/login-register/types/AuthMode.ts";

export function validateAuth(

    email: string,
    password: string,
    confirmPassword: string,
    mode: AuthMode
): Record<string, string> {
    const errors: Record<string, string> = {};

    if (!email.trim()) errors.email = "Email é obrigatório.";
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) errors.email = "Formato de email inválido.";

    if (!password) errors.password = "Senha é obrigatória.";
    else if (password.length < 8) errors.password = "Senha deve ter ao menos 8 caracteres.";

    if (mode === "register" && confirmPassword !== password)
        errors.confirmPassword = "As senhas não coincidem.";

    return errors;
}
