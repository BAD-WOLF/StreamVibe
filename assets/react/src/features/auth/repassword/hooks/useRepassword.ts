import {
    requestPasswordReset, resetPassword, validateResetToken,
} from "@auth/repassword/services/repassword.service.ts";
import {RepasswordMode, type RepasswordModeType,} from "@auth/repassword/types/RepasswordMode.ts";
import type {RepasswordRequestResponse, RepasswordValidateResponse} from "@shared/types/auth"
import type {UseRepasswordReturn} from "@auth/repassword/types/UseRepasswordReturn.ts";
import {validateRepasswordFields} from "@auth/repassword/utils/validateRepassword.ts";
import {calculatePasswordStrength} from '@auth/utils/passwordStrength.service.ts'
import {type Dispatch, type FormEvent, type SetStateAction, useCallback, useEffect, useState} from "react";
import { t } from "i18next";

export function useRepassword(
    onSuccess: () => void,
    locale: string,
    initialToken?: string
): UseRepasswordReturn {
    const hasToken: boolean = Boolean(initialToken);

    const [mode, setMode]: (RepasswordModeType | Dispatch<SetStateAction<RepasswordModeType>>)[] = useState<RepasswordModeType>(
        hasToken ? RepasswordMode.Validate : RepasswordMode.Request
    );
    const [isRequested, setIsRequested]: (boolean | Dispatch<SetStateAction<boolean>>)[] = useState(hasToken);
    const [isDone, setIsDone]: (boolean | Dispatch<SetStateAction<boolean>>)[] = useState(false);

    const [email, setEmail]: (string | Dispatch<SetStateAction<string>>)[] = useState("");
    const [token, setToken]: (string | Dispatch<SetStateAction<string>>)[] = useState(initialToken || "");
    const [password, setPassword]: (string | Dispatch<SetStateAction<string>>)[] = useState("");
    const [confirmPassword, setConfirmPassword]: (string | Dispatch<SetStateAction<string>>)[] = useState("");
    const [errors, setErrors]: (Record<string, string> | Dispatch<SetStateAction<Record<string, string>>>)[] = useState<Record<string, string>>({});
    const [isLoading, setIsLoading]: (boolean | Dispatch<SetStateAction<boolean>>)[] = useState(false);
    const [strength, setStrength]: (number | Dispatch<SetStateAction<number>>)[] = useState(0);
    const [showPassword, setShowPassword]: (boolean | Dispatch<SetStateAction<boolean>>)[] = useState(false);

    useEffect((): void => {
        setStrength(calculatePasswordStrength(password));
    }, [password]);

    // clear errors whenever the mode changes
    useEffect((): void => {
        setErrors({});
    }, [mode]);

    // 1) Request email
    const handleRequest: (e: FormEvent<HTMLFormElement>) => Promise<void> = useCallback(
        async (e: FormEvent<HTMLFormElement>): Promise<void> => {
            e.preventDefault();
            const validation: Record<string, string> = validateRepasswordFields(
                RepasswordMode.Request,
                email,
                "",
                ""
            );
            setErrors(validation);
            if (Object.keys(validation).length) return;

            setIsLoading(true);
            try {
                const resp: RepasswordRequestResponse = await requestPasswordReset(locale, {email});
                if (resp.success) {
                    setIsRequested(true);
                } else {
                    setErrors({email: t("Failed to send email")});
                }
            } catch (err: unknown) {
                const message: string =
                    err instanceof Error ? err.message : t("Unexpected error. Please try again.");
                setErrors({email: message});
            } finally {
                setIsLoading(false);
            }
        },
        [email]
    );

    // 2) Validate token
    const handleValidate: () => Promise<void> = useCallback(async (): Promise<void> => {
        setIsLoading(true);
        try {
            const resp: RepasswordValidateResponse = await validateResetToken(locale, {token: token});
            if (resp.success) {
                setMode(RepasswordMode.Reset);
            } else {
                setErrors({token: t("Invalid or expired token")});
            }
        } catch (err: unknown) {
            const message: string =
                err instanceof Error ? err.message : t("Unexpected error. Please try again.");
            setErrors({token: message});
        } finally {
            setIsLoading(false);
        }
    }, [token]);

    // 3) Reset password
    const handleReset: (e: FormEvent<HTMLFormElement>) => Promise<void> = useCallback(
        async (e: FormEvent<HTMLFormElement>): Promise<void> => {
            e.preventDefault();
            const validation: Record<string, string> = validateRepasswordFields(
                RepasswordMode.Reset,
                "",
                password,
                confirmPassword
            );
            setErrors(validation);
            if (Object.keys(validation).length) return;

            setIsLoading(true);
            try {
                await resetPassword(locale, {token, plainPassword: password});
                setIsDone(true);
                onSuccess();
            } catch (err: unknown) {
                const message: string =
                    err instanceof Error ? err.message : t("Unexpected error. Please try again.");
                setErrors({password: message});
            } finally {
                setIsLoading(false);
            }
        },
        [token, password, confirmPassword, onSuccess]
    );

    return {
        mode,
        isRequested,
        isDone,
        email,
        token,
        password,
        strength,
        confirmPassword,
        errors,
        isLoading,
        showPassword,
        setShowPassword,
        setMode,
        setIsRequested,
        setIsDone,
        setEmail,
        setToken,
        setPassword,
        setConfirmPassword,
        handleRequest,
        handleValidate,
        handleReset,
    };
}
