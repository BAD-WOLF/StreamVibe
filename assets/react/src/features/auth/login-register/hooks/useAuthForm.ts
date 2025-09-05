// src/features/auth/login-register/hooks/useAuthForm.ts
import { useEffect, useState, useCallback, type FormEvent } from "react";
import { calculatePasswordStrength } from "@auth/utils/passwordStrength.service.ts";
import { validateAuth } from "@auth/login-register/utils/validateAuth.ts";
import type { UseAuthFormReturn } from "@auth/login-register/types/UseAuthFormReturn.ts";
import type { AuthMode } from "@auth/login-register/types/AuthMode.ts";
import { t } from "i18next";

// services that communicate with the API
import { loginService, registerService } from "@auth/login-register/services/auth.service.ts";
import type { LoginResponse } from "@shared/types/auth/responses/login-register/login.ts";

/**
 * Hook shared for Login / Register
 * - does not change the signature (onSubmit: () => void)
 * - when login is successful, stores token + refresh_token in localStorage (default)
 *   — you can switch to secure cookie or other storage according to app policy
 */
export function useAuthForm(onSubmit: () => void, locale: string, mode: AuthMode): UseAuthFormReturn {
    const [email, setEmail] = useState<string>("");
    const [password, setPassword] = useState<string>("");
    const [confirmPassword, setConfirmPassword] = useState<string>("");
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [showPassword, setShowPassword] = useState<boolean>(false);
    const [strength, setStrength] = useState<number>(0);
    const [isLoading, setIsLoading] = useState<boolean>(false);

    // calculates password strength reactively
    useEffect(() => {
        setStrength(calculatePasswordStrength(password));
    }, [password]);

    // memoized handler to avoid re-creation on every render
    const handleSubmit = useCallback(
        async (e: FormEvent<HTMLFormElement>) => {
            e.preventDefault();

            // local validation before calling the API
            const validation = validateAuth(email, password, confirmPassword, mode);
            setErrors(validation);
            if (Object.keys(validation).length !== 0) return;

            setIsLoading(true);
            try {
                if (mode === "login") {
                    // calls the real login service
                    const resp: LoginResponse = await loginService(locale, { email, password });

                    // store tokens: for simplicity we use localStorage here,
                    // but in production prefer httpOnly+secure cookie or a secure storage.
                    try {
                        localStorage.setItem("token", resp.token);
                        localStorage.setItem("refresh_token", resp.refresh_token);
                    } catch {
                        // if storage fails, does not block flow; but log if you want
                        // console.warn("Failed to persist tokens to storage");
                    }

                    // optional: configure a global HTTP client (axios/fetch wrapper)
                    // to automatically send Authorization header.
                    // Commented example:
                    // apiClient.defaults.headers.common['Authorization'] = `Bearer ${resp.token}`;

                    // notifies hook consumer that login was successful
                    onSubmit();
                } else {
                    // register -> just calls the service and waits for success message
                    await registerService(locale, { email, password });
                    // after registration, you can:
                    // - redirect to login;
                    // - automatically log the user in (if API returns token);
                    // here we call onSubmit for action defined by consumer (redirect, toast, etc.)
                    onSubmit();
                }
            } catch (err: unknown) {
                // standard error message
                const message =
                    err instanceof Error
                        ? err.message
                        : t("Unexpected error. Please try again later.");
                // set error on `form` key for global messages
                setErrors((prev) => ({ ...prev, form: message }));
            } finally {
                setIsLoading(false);
            }
        },
        [email, password, confirmPassword, mode, onSubmit]
    );

    return {
        email,
        password,
        confirmPassword,
        errors,
        strength,
        showPassword,
        isLoading,
        setEmail,
        setPassword,
        setConfirmPassword,
        setShowPassword,
        handleSubmit,
    };
}
