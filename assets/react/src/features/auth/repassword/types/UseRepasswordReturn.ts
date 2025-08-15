import type {RepasswordModeType} from "@auth/repassword/types/RepasswordMode.ts";
import type {FormEvent} from "react";

export type UseRepasswordReturn = {
    mode: RepasswordModeType;
    isRequested: boolean;
    isDone: boolean;
    email: string;
    token: string;
    password: string;
    strength: number;
    confirmPassword: string;
    errors: Record<string, string>;
    isLoading: boolean;
    showPassword: boolean;
    setShowPassword: (v: boolean) => void;
    setMode: (mode: RepasswordModeType) => void
    setIsRequested: (isRequested: boolean) => void;
    setIsDone: (isDone: boolean) => void;
    setEmail: (v: string) => void;
    setToken: (v: string) => void;
    setPassword: (v: string) => void;
    setConfirmPassword: (v: string) => void;
    handleRequest: (e: FormEvent<HTMLFormElement>) => Promise<void>;
    handleValidate: () => Promise<void>;
    handleReset: (e: FormEvent<HTMLFormElement>) => Promise<void>;
}
