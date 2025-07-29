import type {UseRepasswordReturn} from '@auth/repassword/types/UseRepasswordReturn';
import InputFieldGroup from '@shared/components/auth/InputFieldGroup.tsx'
import PasswordStrengthBar from '@shared/components/auth/PasswordStrengthBar';
import * as styles from "@shared/styles/ts/login/LoginPageStyles";
import type {InputField} from '@shared/types/InputField';
import type {ReactElement} from 'react';
import {FaLock} from 'react-icons/fa';

export default function ResetPasswordForm({
    password,
    strength,
    confirmPassword,
    errors,
    isLoading,
    setPassword,
    setShowPassword,
    showPassword,
    setConfirmPassword,
    handleReset,
}: Pick<
    UseRepasswordReturn,
    | 'password'
    | 'strength'
    | 'confirmPassword'
    | 'errors'
    | 'isLoading'
    | 'setPassword'
    | 'setShowPassword'
    | 'showPassword'
    | 'setConfirmPassword'
    | 'handleReset'
>): ReactElement {
    const fields: InputField[] = [
        {
            id: 'password',
            type: showPassword ? 'text' : 'password',
            placeholder: 'Nova senha',
            value: password,
            setter: setPassword,
            icon: <FaLock/>,
            error: errors.password,
        },
        {
            id: 'confirmPassword',
            type: 'confirmPassword',
            placeholder: 'Confirmar nova senha',
            value: confirmPassword,
            setter: setConfirmPassword,
            icon: <FaLock/>,
            error: errors.confirmPassword,
        },
    ];

    return (
        <form onSubmit={handleReset} className="space-y-4">
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

            <PasswordStrengthBar strength={strength}/>

            <button
                type="submit"
                disabled={isLoading}
                className={`${styles.buttonBase} bg-green-600 text-white rounded ${
                    isLoading ? 'opacity-50 cursor-not-allowed' : 'hover:opacity-90'
                }`}
            >
                {isLoading ? 'Redefinindo...' : 'Redefinir senha'}
            </button>
        </form>
    );
}
