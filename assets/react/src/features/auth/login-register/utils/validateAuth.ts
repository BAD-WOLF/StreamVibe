import type {AuthMode} from "@auth/login-register/types/AuthMode.ts";
import { t } from "i18next";

export function validateAuth(

    email: string,
    password: string,
    confirmPassword: string,
    mode: AuthMode
): Record<string, string> {
    const errors: Record<string, string> = {};

    if (!email.trim()) errors.email = t("Email is required.");
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) errors.email = t("Invalid email format.");

    if (!password) errors.password = t("Password is required.");
    else if (password.length < 8) errors.password = t("Password must be at least 8 characters long.");

    if (mode === "register" && confirmPassword !== password)
        errors.confirmPassword = t("Passwords do not match.");

    return errors;
}
