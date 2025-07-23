import { useEffect, useState, type FormEvent } from "react";
import { calculatePasswordStrength } from "@auth/login-register/services/passwordStrength.service.ts";
import { validateAuth } from "@auth/login-register/utils/validateAuth.ts";
import type { UseAuthFormReturn } from "@auth/login-register/types/UseAuthFormReturn.ts";
import type { AuthMode } from "@auth/login-register/types/AuthMode.ts";

export function useAuthForm(onSubmit: () => void, mode: AuthMode): UseAuthFormReturn {
    const [email, setEmail] = useState("");
    const [password, setPassword] = useState("");
    const [confirmPassword, setConfirmPassword] = useState("");
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [showPassword, setShowPassword] = useState(false);
    const [strength, setStrength] = useState(0);

    useEffect(() => {
        setStrength(calculatePasswordStrength(password));
    }, [password]);

    const handleSubmit = (e: FormEvent<HTMLFormElement>): void => {
        e.preventDefault();
        const validation = validateAuth(email, password, confirmPassword, mode);
        setErrors(validation);
        if (Object.keys(validation).length === 0) onSubmit();
    };

    return {
        email,
        password,
        confirmPassword,
        errors,
        strength,
        showPassword,
        setEmail,
        setPassword,
        setConfirmPassword,
        setShowPassword,
        handleSubmit,
    };
}
