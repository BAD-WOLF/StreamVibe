import type {UseRepasswordReturn} from '@auth/repassword/types/UseRepasswordReturn.ts';
import type {ChangeEvent, ReactElement} from "react";

export default function EmailRequestForm({
    email, errors, isLoading, setEmail, handleRequest,
}: Pick<UseRepasswordReturn, "email" | "errors" | "isLoading" | "setEmail" | "handleRequest">): ReactElement {
    return (
        <form onSubmit={handleRequest} className="space-y-4">
            <input
                type="email"
                placeholder="Seu email"
                value={email}
                onChange={(e: ChangeEvent<HTMLInputElement>): void => setEmail(e.target.value)}
                disabled={isLoading}
                className="w-full p-2 border rounded"
            />
            {errors.email && <p className="text-red-500">{errors.email}</p>}
            <button
                type="submit"
                disabled={isLoading}
                className="w-full py-2 bg-blue-600 text-white rounded"
            >
                {isLoading ? "Enviando..." : "Enviar email de redefinição"}
            </button>
        </form>
    );
}
