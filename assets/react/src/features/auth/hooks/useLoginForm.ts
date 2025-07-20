// Hook that manages login form state, validation, and password strength

import {calculatePasswordStrength} from "@auth/services/passwordStrength.service";
import type {UseLoginFormReturn} from "@auth/types/UseLoginFormReturn.ts";
import {validateLoginFields} from "@auth/utils/validateLogin";
import type {FormEvent} from "react";
import {useEffect, useState} from 'react';

export function useLoginForm(onSubmit: () => void): UseLoginFormReturn {
    const [email, setEmail] = useState("");
    const [password, setPassword] = useState("");
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [showPassword, setShowPassword] = useState(false);
    const [strength, setStrength] = useState(0);

    useEffect((): void => {
        const passwordStrength: number = calculatePasswordStrength(password);
        setStrength(passwordStrength);
    }, [password]);

    const handleSubmit = (e: FormEvent<HTMLFormElement>): void => {
        e.preventDefault();
        const validation = validateLoginFields(email, password);
        setErrors(validation);
        if (Object.keys(validation).length === 0) onSubmit();
    };

    return {
        email,
        password,
        errors,
        strength,
        showPassword,
        setEmail,
        setPassword,
        setShowPassword,
        handleSubmit,
    };
}
