import { Form } from '@inertiajs/react';
import type { ComponentProps } from 'react';

type AuthFormProps = ComponentProps<typeof Form>;

export function AuthForm(props: AuthFormProps) {
    return <Form {...props} noValidate />;
}
