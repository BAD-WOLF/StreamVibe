// src/features/auth/login-register/hooks/useAuthForm.ts
import { useEffect, useState, useCallback, type FormEvent } from "react";
import { calculatePasswordStrength } from "@auth/utils/passwordStrength.service.ts";
import { validateAuth } from "@auth/login-register/utils/validateAuth.ts";
import type { UseAuthFormReturn } from "@auth/login-register/types/UseAuthFormReturn.ts";
import type { AuthMode } from "@auth/login-register/types/AuthMode.ts";

// serviços que conversam com a API
import { loginService, registerService } from "@auth/login-register/services/auth.service.ts";
import type { LoginResponse } from "@shared/types/auth/responses/login-register/login.ts";

/**
 * Hook compartilhado para Login / Register
 * - não altera a assinatura (onSubmit: () => void)
 * - quando login for bem sucedido, armazena token + refresh_token no localStorage (padrão)
 *   — você pode trocar para cookie seguro ou outro storage conforme política do app
 */
export function useAuthForm(onSubmit: () => void, mode: AuthMode): UseAuthFormReturn {
    const [email, setEmail] = useState<string>("");
    const [password, setPassword] = useState<string>("");
    const [confirmPassword, setConfirmPassword] = useState<string>("");
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [showPassword, setShowPassword] = useState<boolean>(false);
    const [strength, setStrength] = useState<number>(0);
    const [isLoading, setIsLoading] = useState<boolean>(false);

    // calcula força da senha reativamente
    useEffect(() => {
        setStrength(calculatePasswordStrength(password));
    }, [password]);

    // handler memoizado para evitar re-criação a cada render
    const handleSubmit = useCallback(
        async (e: FormEvent<HTMLFormElement>) => {
            e.preventDefault();

            // validação local antes de chamar a API
            const validation = validateAuth(email, password, confirmPassword, mode);
            setErrors(validation);
            if (Object.keys(validation).length !== 0) return;

            setIsLoading(true);
            try {
                if (mode === "login") {
                    // chama o serviço real de login
                    const resp: LoginResponse = await loginService({ email, password });

                    // armazenar tokens: por simplicidade usamos localStorage aqui,
                    // mas em produção privilegie cookie httpOnly+secure ou um storage seguro.
                    try {
                        localStorage.setItem("token", resp.token);
                        localStorage.setItem("refresh_token", resp.refresh_token);
                    } catch {
                        // se storage falhar, não impede o fluxo; mas registre se quiser
                        // console.warn("Failed to persist tokens to storage");
                    }

                    // opcional: configurar um cliente HTTP global (axios/fetch wrapper)
                    // para enviar Authorization header automatiquement.
                    // Exemplo comentado:
                    // apiClient.defaults.headers.common['Authorization'] = `Bearer ${resp.token}`;

                    // notifica consumidor do hook que login foi bem sucedido
                    onSubmit();
                } else {
                    // register -> apenas chama o serviço e espera mensagem de sucesso
                    await registerService({ email, password });
                    // após registro, você pode:
                    // - redirecionar para login;
                    // - automaticamente logar o usuário (se a API retornar token);
                    // aqui chamamos onSubmit para ação definida pelo consumidor (redirect, toast, etc.)
                    onSubmit();
                }
            } catch (err: unknown) {
                // uniformiza mensagem de erro
                const message =
                    err instanceof Error
                        ? err.message
                        : "Erro inesperado. Tente novamente mais tarde.";
                // setamos erro na chave `form` para mensagens globais
                setErrors((prev) => ({ ...prev, form: message }));
            } finally {
                setIsLoading(false);
            }
        },
        [email, password, confirmPassword, mode, onSubmit]
    );

    return {
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
    };
}
