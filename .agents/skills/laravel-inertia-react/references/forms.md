# Form Handling

## Table of Contents

- [Form Component](#form-component)
- [Render Props API](#render-props-api)
- [Form Props](#form-props)
- [Common Patterns](#common-patterns)
- [Error Display](#error-display)
- [Loading States](#loading-states)
- [Forms with Refs](#forms-with-refs)
- [Form in Dialog](#form-in-dialog)

## Form Component

The `<Form>` component is the recommended way to build forms with Inertia. Use Wayfinder's `.form()` method for type-safe form submission:

```tsx
import { Form } from '@inertiajs/react';
import { store } from '@/routes/resource';
// or import Controller from '@/actions/App/Http/Controllers/ResourceController';

<Form {...store.form()}>
    {({ processing, errors }) => (
        <>
            <input name="title" />
            {errors.title && <span>{errors.title}</span>}
            <button disabled={processing}>Submit</button>
        </>
    )}
</Form>
```

The `.form()` method returns `{ action: string, method: string }` which spreads onto the Form.

## Render Props API

The Form component provides these render props:

| Prop | Type | Description |
|------|------|-------------|
| `errors` | `Record<string, string>` | Validation errors keyed by field name |
| `hasErrors` | `boolean` | Whether any errors exist |
| `processing` | `boolean` | Form is submitting |
| `wasSuccessful` | `boolean` | Form submitted successfully (persists) |
| `recentlySuccessful` | `boolean` | Form submitted successfully (resets after 2s) |
| `clearErrors` | `(...fields?) => void` | Clear specific or all errors |
| `resetAndClearErrors` | `(...fields?) => void` | Reset fields and clear errors |
| `defaults` | `(data) => void` | Set default values for reset |

## Form Props

| Prop | Type | Description |
|------|------|-------------|
| `resetOnSuccess` | `boolean \| string[]` | Reset all or specific fields on success |
| `resetOnError` | `boolean \| string[]` | Reset all or specific fields on error |
| `setDefaultsOnSuccess` | `boolean` | Update defaults to current values on success |
| `options` | `object` | Inertia visit options |
| `onError` | `(errors) => void` | Called when validation errors occur |
| `onSuccess` | `(page) => void` | Called on successful submission |
| `onStart` | `() => void` | Called when submission starts |
| `onFinish` | `() => void` | Called when submission completes |

## Common Patterns

### Reset Password Field on Error

```tsx
<Form
    {...store.form()}
    resetOnError={['password', 'password_confirmation']}
>
```

### Reset All Fields on Success

```tsx
<Form
    {...store.form()}
    resetOnSuccess
>
```

### Preserve Scroll Position

```tsx
<Form
    {...Controller.update.form()}
    options={{ preserveScroll: true }}
>
```

### Success Message with Transition

```tsx
import { Transition } from '@headlessui/react';

<Form {...update.form()}>
    {({ processing, recentlySuccessful }) => (
        <>
            <Button disabled={processing}>Save</Button>
            <Transition
                show={recentlySuccessful}
                enter="transition ease-in-out"
                enterFrom="opacity-0"
                leave="transition ease-in-out"
                leaveTo="opacity-0"
            >
                <p className="text-sm text-neutral-600">Saved</p>
            </Transition>
        </>
    )}
</Form>
```

## Error Display

Use the `InputError` component to display field errors:

```tsx
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

<Form {...store.form()}>
    {({ errors }) => (
        <div className="grid gap-2">
            <Label htmlFor="email">Email</Label>
            <Input
                id="email"
                type="email"
                name="email"
                required
                autoComplete="email"
                placeholder="email@example.com"
            />
            <InputError message={errors.email} />
        </div>
    )}
</Form>
```

## Loading States

Use the `Spinner` component inside buttons during form submission:

```tsx
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';

<Form {...store.form()}>
    {({ processing }) => (
        <Button type="submit" disabled={processing}>
            {processing && <Spinner />}
            Submit
        </Button>
    )}
</Form>
```

## Forms with Refs

Use refs for focus management on errors:

```tsx
import { useRef } from 'react';

export default function PasswordForm() {
    const passwordInput = useRef<HTMLInputElement>(null);
    const currentPasswordInput = useRef<HTMLInputElement>(null);

    return (
        <Form
            {...PasswordController.update.form()}
            resetOnError={['password', 'password_confirmation', 'current_password']}
            onError={(errors) => {
                if (errors.password) {
                    passwordInput.current?.focus();
                }
                if (errors.current_password) {
                    currentPasswordInput.current?.focus();
                }
            }}
        >
            {({ errors }) => (
                <>
                    <Input
                        ref={currentPasswordInput}
                        name="current_password"
                        type="password"
                    />
                    <InputError message={errors.current_password} />

                    <Input
                        ref={passwordInput}
                        name="password"
                        type="password"
                    />
                    <InputError message={errors.password} />
                </>
            )}
        </Form>
    );
}
```

## Form in Dialog

Pattern for forms inside dialogs with cancel functionality:

```tsx
import { Form } from '@inertiajs/react';
import { useRef } from 'react';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import InputError from '@/components/input-error';
import Controller from '@/actions/App/Http/Controllers/ResourceController';

export default function DeleteResource() {
    const passwordInput = useRef<HTMLInputElement>(null);

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button variant="destructive">Delete</Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Confirm Deletion</DialogTitle>
                <DialogDescription>
                    This action cannot be undone. Enter your password to confirm.
                </DialogDescription>

                <Form
                    {...Controller.destroy.form()}
                    options={{ preserveScroll: true }}
                    onError={() => passwordInput.current?.focus()}
                    resetOnSuccess
                >
                    {({ resetAndClearErrors, processing, errors }) => (
                        <>
                            <Input
                                type="password"
                                name="password"
                                ref={passwordInput}
                                placeholder="Password"
                            />
                            <InputError message={errors.password} />

                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button
                                        variant="secondary"
                                        onClick={() => resetAndClearErrors()}
                                    >
                                        Cancel
                                    </Button>
                                </DialogClose>

                                <Button
                                    variant="destructive"
                                    type="submit"
                                    disabled={processing}
                                >
                                    Delete
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
```

Key points:
- Use `resetAndClearErrors()` on Cancel to reset form state
- Use `onError` callback to focus the relevant field
- Use `resetOnSuccess` to clear form after successful submission
- Use `preserveScroll: true` in options to maintain scroll position
