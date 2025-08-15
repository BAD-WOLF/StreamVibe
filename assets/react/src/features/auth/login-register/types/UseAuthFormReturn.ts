import type { FormEvent } from "react";

export interface UseAuthFormReturn {
    // states
    email: string;
    password: string;
    confirmPassword: string;
    errors: Record<string, string>;
    strength: number;
    showPassword: boolean;
    isLoading: boolean;
    // setters
    setEmail: (value: string) => void;
    setPassword: (value: string) => void;
    setConfirmPassword: (value: string) => void;
    setShowPassword: (value: boolean) => void;
    // handles
    handleSubmit: (e: FormEvent<HTMLFormElement>) => void;
}
