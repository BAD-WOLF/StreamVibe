import type {ReactElement} from "react";

export default function RepasswordSuccess(): ReactElement {
    return (
        <div className="text-center py-20">
            <h2 className="text-2xl font-bold text-green-500">
                Sua senha foi alterada com sucesso!
            </h2>
            <p className="mt-4 text-white/80">
                Você já pode fazer login com sua nova senha.
            </p>
        </div>
    );
}
