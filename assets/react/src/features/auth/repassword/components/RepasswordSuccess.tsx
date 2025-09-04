import type {ReactElement} from "react";
import { Trans } from 'react-i18next';

export default function RepasswordSuccess(): ReactElement {
    return (
        <div className="text-center py-20">
            <h2 className="text-2xl font-bold text-green-500">
                <Trans>Your password has been successfully changed!</Trans>
            </h2>
            <p className="mt-4 text-white/80">
                <Trans>You can now log in with your new password.</Trans>
            </p>
        </div>
    );
}
