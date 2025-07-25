import PasswordStrengthBar from "@auth/login-register/components/PasswordStrengthBar.tsx";
import {useAuthForm} from "@auth/login-register/hooks/useAuthForm.ts";
import type {AuthMode} from "@auth/login-register/types/AuthMode.ts";
import * as styles from "@shared/styles/ts/login/LoginPageStyles.ts";
import type {InputField} from "@shared/types/InputField.ts";
import type {ChangeEvent, HTMLAttributes, ReactElement} from "react";
import {cloneElement} from "react";
import {FaEnvelope, FaEye, FaEyeSlash, FaLock} from "react-icons/fa";

export default function AuthForm({onSubmit, mode}: { onSubmit: () => void; mode: AuthMode; }): ReactElement {
    const {
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
    } = useAuthForm(onSubmit, mode);

    const fields: InputField[] = [
        {
            id: "email",
            type: "email",
            placeholder: "Email",
            value: email,
            setter: setEmail,
            icon: <FaEnvelope/>,
            error: errors.email,
        },
        {
            id: "password",
            type: showPassword ? "text" : "password",
            placeholder: "Senha",
            value: password,
            setter: setPassword,
            icon: <FaLock/>,
            error: errors.password,
        },
        ...(mode === "register"
                ? [{
                    id: "confirmPassword",
                    type: showPassword ? "text" : "password",
                    placeholder: "Confirmar Senha",
                    value: confirmPassword,
                    setter: setConfirmPassword,
                    icon: <FaLock/>,
                    error: errors.confirmPassword,
                }] as const : []
        ),
    ];

    return (
        <form onSubmit={handleSubmit} noValidate>
            {fields.map((field) => {
                const focused = document.activeElement?.id === field.id;
                const hasError = !!field.error;
                const attrClass: HTMLAttributes<HTMLElement> = {className: `${styles.iconBase} left-3`};

                return (
                    <div key={field.id}
                         className={`${hasError ? "shake" : ""} ${styles.inputContainer(focused, hasError)}`}>
                        {cloneElement(field.icon, attrClass)}
                        <input
                            id={field.id}
                            type={field.type}
                            placeholder={field.placeholder}
                            value={field.value}
                            onChange={(e: ChangeEvent<HTMLInputElement>) => field.setter(e.target.value)}
                            className={styles.inputClasses(hasError)}
                        />
                        {field.id.includes("password") && (
                            <span
                                onClick={() => setShowPassword(!showPassword)}
                                className={`${styles.iconBase} right-3 cursor-pointer`}
                            >
                {showPassword ? <FaEyeSlash/> : <FaEye/>}
              </span>
                        )}
                        {field.error && <p className="text-red-500 text-sm mt-1">{field.error}</p>}
                    </div>
                );
            })}

            {password && <PasswordStrengthBar strength={strength}/>}

            <button type="submit" className={`${styles.buttonBase} ${styles.gradients?.gradiente1 || ""} text-white`}>
                {mode === "login" ? "Entrar" : "Registrar"}
            </button>
        </form>
    );
}
