import {useLoginForm} from "@auth/hooks/useLoginForm";
import * as styles from "@shared/styles/ts/login/LoginPageStyles";
import type {InputField} from "@shared/types/InputField.ts";
import {type ChangeEvent, cloneElement, type HTMLAttributes, type ReactElement} from "react";
import {FaEnvelope, FaEye, FaEyeSlash, FaLock} from "react-icons/fa";
import PasswordStrengthBar from "./PasswordStrengthBar";

export default function LoginForm({onSubmit}: { onSubmit: () => void; }): ReactElement {
    const {
        email, password, errors, strength,
        showPassword, setEmail, setPassword,
        setShowPassword, handleSubmit
    } = useLoginForm(onSubmit);

    const fields: Array<InputField> = [
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
    ];

    return (
        <form onSubmit={handleSubmit} noValidate>
            {fields.map((field: InputField): ReactElement => {
                const focused: boolean = document.activeElement?.id === field.id;
                const hasError: boolean = !!field.error;
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
                            onChange={(e: ChangeEvent<HTMLInputElement>): void => field.setter(e.target.value)}
                            className={styles.inputClasses(hasError)}
                        />
                        {field.id === "password" && (
                            <span
                                onClick={(): void => setShowPassword(!showPassword)}
                                className={`${styles.iconBase} right-3 cursor-pointer`}>
                                {showPassword ? <FaEyeSlash/> : <FaEye/>}
                            </span>
                        )}
                        {field.error && <p className="text-red-500 text-sm mt-1">{field.error}</p>}
                    </div>
                );
            })}

            {password && <PasswordStrengthBar strength={strength}/>}

            <button type="submit"
                    className={`${styles.buttonBase} ${styles.gradients?.gradiente1 || ""} text-white hover:scale-105`}>
                Entrar
            </button>
        </form>
    );
}
