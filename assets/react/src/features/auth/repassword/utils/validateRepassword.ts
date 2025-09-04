import {RepasswordMode, type RepasswordModeType} from "@auth/repassword/types/RepasswordMode.ts";
import { t } from "i18next";

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

    // email validation only in Request
    isRequest && !emailTrimmed && (errors.email = t("Email is required."));
    isRequest && emailTrimmed && !emailRegex.test(emailTrimmed)
    && (errors.email = t("Invalid email format."));

    // password validation in both cases (here only Reset, since in Request it doesn't need a password)
    isReset && !password && (errors.password = t("Password is required."));
    isReset && password && password.length < 8 && (errors.password = t("Password must be at least 8 characters long."));
    isReset && confirmPassword !== password && (errors.confirmPassword = t("Passwords do not match."));

    return errors;
}
