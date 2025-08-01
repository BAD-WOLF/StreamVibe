import * as styles from "@shared/styles/ts/login/LoginPageStyles";
import type {InputField} from "@shared/types/InputField";
import type {ChangeEvent, HTMLAttributes, ReactElement} from "react";
import {cloneElement} from "react";
import {FaEye, FaEyeSlash} from "react-icons/fa";

export default function InputFieldGroup({
    id,
    type,
    placeholder,
    value,
    setter,
    icon,
    error,
    isLoading,
    showPassword,
    setShowPassword,
}: InputField & {
    isLoading: boolean;
    showPassword?: boolean;
    setShowPassword?: (v: boolean) => void;
}): ReactElement {
    const focused: boolean = document.activeElement?.id === id;
    const hasError: boolean = !!error;
    const attrClass: HTMLAttributes<HTMLElement> = {
        className: `${styles.iconBase} left-3`,
    };

    return (
        <div className={`${hasError ? "shake" : ""} ${styles.inputContainer(focused, hasError)}`}>
            {cloneElement(icon, attrClass)}

            <input
                id={id}
                type={type}
                placeholder={placeholder}
                value={value}
                onChange={(e: ChangeEvent<HTMLInputElement>): void => setter(e.target.value)}
                disabled={isLoading}
                className={styles.inputClasses(hasError)}
            />

            <span
                onClick={(): void => setShowPassword?.(!showPassword)}
                className={`${styles.iconBase} right-3 cursor-pointer`}
            >
        {id === "password"
            ? type === "password"
                ? <FaEye/>
                : <FaEyeSlash/>
            : null}
      </span>

            {error && <p className="text-red-500 text-sm mt-1">{error}</p>}
        </div>
    );
}
