import type { ComponentProps, ElementType } from 'react';
import RawDatePicker from 'react-multi-date-picker';

type DatePickerComponent = ElementType<
    ComponentProps<typeof RawDatePicker>
>;

function resolveDatePickerComponent(
    moduleValue: typeof RawDatePicker,
): DatePickerComponent {
    const candidate = (
        moduleValue as typeof RawDatePicker & {
            default?: DatePickerComponent;
        }
    ).default;

    if (candidate && typeof candidate === 'object' && 'render' in candidate) {
        return candidate;
    }

    if (candidate && typeof candidate === 'function') {
        return candidate;
    }

    return moduleValue as DatePickerComponent;
}

const DatePicker = resolveDatePickerComponent(RawDatePicker);

export default DatePicker;
