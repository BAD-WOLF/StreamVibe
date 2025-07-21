import type { FormEvent} from "react";

export type UseLoginFormReturn = {
    email: string;
    password: string;
    errors: Record<string, string>;
    strength: number;
    showPassword: boolean;
    setEmail: (value: string) => void;
    setPassword: (value: string) => void;
    setShowPassword: (value: boolean) => void;
    handleSubmit: (e: FormEvent<HTMLFormElement>) => void;
};