import type { ReactNode } from 'react';

type AdminPageHeaderProps = {
    title: string;
    description?: string;
    actions?: ReactNode;
};

export function AdminPageHeader({
    title,
    description,
    actions,
}: AdminPageHeaderProps) {
    return (
        <div className="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 className="font-liana text-xl text-text">{title}</h2>
                {description ? (
                    <p className="mt-1 text-sm text-muted">{description}</p>
                ) : null}
            </div>
            {actions ? <div className="flex shrink-0 gap-2">{actions}</div> : null}
        </div>
    );
}
