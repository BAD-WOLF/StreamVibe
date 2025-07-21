// Reusable horizontal movie grid in sections

import type {FC, ReactElement, ReactNode} from "react";

interface MovieGridProps {
    children: ReactNode;
    itemCount?: number;
}

export const MovieGrid: FC<MovieGridProps> = ({children}: MovieGridProps): ReactElement => (
    <div className="flex overflow-x-auto overflow-y-hidden gap-6 pb-2">
        {children}
    </div>
);
