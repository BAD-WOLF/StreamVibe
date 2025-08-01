import {RepasswordMode, type RepasswordModeType} from "@auth/repassword/types/RepasswordMode.ts";

export function validateRepasswordFields(
    mode: RepasswordModeType,
    email: string,
    password: string,
    confirmPassword: string
): Record<string, string> {
    const errors: Record<string, string> = {};

    const isRequest = mode === RepasswordMode.Request;
    const isReset = mode === RepasswordMode.Reset;

    const emailTrimmed = email.trim();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    // validação de email apenas em Request
    isRequest && !emailTrimmed && (errors.email = "Email é obrigatório.");
    isRequest && emailTrimmed && !emailRegex.test(emailTrimmed)
    && (errors.email = "Formato de email inválido.");

    // validação de senha nos dois casos (aqui só Reset, pois em Request não precisa senha)
    isReset && !password && (errors.password = "Senha é obrigatória.");
    isReset && password && password.length < 8 && (errors.password = "Senha deve ter ao menos 8 caracteres.");
    isReset && confirmPassword !== password && (errors.confirmPassword = "As senhas não coincidem.");

    return errors;
}
