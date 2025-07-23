/* eslint-disable no-unused-vars */
import type {FormEvent} from "react";

export type UseAuthFormReturn = {
    email: string;
    password: string;
    confirmPassword: string;
    errors: Record<string, string>;
    strength: number;
    showPassword: boolean;
    setEmail: (value: string) => void;
    setPassword: (value: string) => void;
    setConfirmPassword: (value: string) => void;
    setShowPassword: (value: boolean) => void;
    handleSubmit: (e: FormEvent<HTMLFormElement>) => void;
};
