// src/features/auth/login-register/components/AuthForm.tsx
import { type ReactElement } from "react";
import { useAuthForm } from "@auth/login-register/hooks/useAuthForm.ts";
import { AuthMode } from "@auth/login-register/types/AuthMode.ts";
import type { UseAuthFormReturn } from "@auth/login-register/types/UseAuthFormReturn.ts";
import InputFieldGroup from "@shared/components/auth/InputFieldGroup.tsx";
import PasswordStrengthBar from "@shared/components/auth/PasswordStrengthBar.tsx";
import * as styles from "@shared/styles/ts/login/LoginPageStyles.ts";
import type { InputField } from "@shared/types/InputField.ts";
import { FaEnvelope, FaLock } from "react-icons/fa";

export default function AuthForm({
    onSubmit,
    mode,
}: {
    onSubmit: () => void;
    mode: AuthMode;
}): ReactElement {
    const {
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
    }: UseAuthFormReturn = useAuthForm(onSubmit, mode);

    const fields: InputField[] = [
        {
            id: "email",
            type: "email",
            placeholder: "Email",
            value: email,
            setter: setEmail,
            icon: <FaEnvelope />,
            error: errors.email,
        },
        {
            id: "password",
            type: showPassword ? "text" : "password",
            placeholder: "Senha",
            value: password,
            setter: setPassword,
            icon: <FaLock />,
            error: errors.password,
        },
        ...(mode === AuthMode.Register
            ? [
                {
                    id: "confirmPassword",
                    type: showPassword ? "text" : "password",
                    placeholder: "Confirmar Senha",
                    value: confirmPassword,
                    setter: setConfirmPassword,
                    icon: <FaLock />,
                    error: errors.confirmPassword,
                },
            ] as const: []),
    ];

    return (
        <form onSubmit={handleSubmit} noValidate>
            {fields.map((field: InputField): ReactElement => (
                <InputFieldGroup
                    key={field.id}
                    id={field.id}
                    type={field.type}
                    placeholder={field.placeholder}
                    value={field.value}
                    setter={field.setter}
                    icon={field.icon}
                    error={field.error}
                    isLoading={isLoading}
                    showPassword={showPassword}
                    setShowPassword={setShowPassword}
                />
            ))}

            {password && <PasswordStrengthBar strength={strength} />}

            {errors.form && <p className="text-red-500 text-sm mt-2">{errors.form}</p>}

            <button
                type="submit"
                disabled={isLoading}
                className={`${styles.buttonBase} ${styles.gradients?.gradiente1 || ""} text-white disabled:opacity-60`}
            >
                {mode === AuthMode.Login ? "Entrar" : "Registrar"}
            </button>
        </form>
    );
}
