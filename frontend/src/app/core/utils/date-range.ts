export type DateRangePreset =
  | 'today'
  | 'yesterday'
  | 'this_week'
  | 'last_week'
  | 'this_month'
  | 'last_month'
  | 'this_quarter'
  | 'last_quarter'
  | 'this_year'
  | 'last_year'
  | 'custom';

export const DATE_RANGE_OPTIONS: Array<{ value: DateRangePreset; label: string }> = [
  { value: 'today', label: 'Hôm nay' },
  { value: 'yesterday', label: 'Hôm qua' },
  { value: 'this_week', label: 'Tuần này' },
  { value: 'last_week', label: 'Tuần trước' },
  { value: 'this_month', label: 'Tháng này' },
  { value: 'last_month', label: 'Tháng trước' },
  { value: 'this_quarter', label: 'Quý này' },
  { value: 'last_quarter', label: 'Quý trước' },
  { value: 'this_year', label: 'Năm này' },
  { value: 'last_year', label: 'Năm trước' },
  { value: 'custom', label: 'Tự chọn' },
];

export function resolveDateRange(preset: DateRangePreset, now = new Date()): { date_from: string; date_to: string } | null {
  const today = startOfDay(now);

  if (preset === 'custom') {
    return null;
  }

  if (preset === 'today') {
    return range(today, today);
  }

  if (preset === 'yesterday') {
    const day = addDays(today, -1);
    return range(day, day);
  }

  if (preset === 'this_week') {
    return range(startOfWeek(today), today);
  }

  if (preset === 'last_week') {
    const start = addDays(startOfWeek(today), -7);
    return range(start, addDays(start, 6));
  }

  if (preset === 'this_month') {
    return range(new Date(today.getFullYear(), today.getMonth(), 1), today);
  }

  if (preset === 'last_month') {
    const start = new Date(today.getFullYear(), today.getMonth() - 1, 1);
    return range(start, new Date(today.getFullYear(), today.getMonth(), 0));
  }

  if (preset === 'this_quarter') {
    const quarterStartMonth = Math.floor(today.getMonth() / 3) * 3;
    return range(new Date(today.getFullYear(), quarterStartMonth, 1), today);
  }

  if (preset === 'last_quarter') {
    const currentQuarterStartMonth = Math.floor(today.getMonth() / 3) * 3;
    const start = new Date(today.getFullYear(), currentQuarterStartMonth - 3, 1);
    return range(start, new Date(start.getFullYear(), start.getMonth() + 3, 0));
  }

  if (preset === 'this_year') {
    return range(new Date(today.getFullYear(), 0, 1), today);
  }

  return range(new Date(today.getFullYear() - 1, 0, 1), new Date(today.getFullYear() - 1, 11, 31));
}

function range(dateFrom: Date, dateTo: Date): { date_from: string; date_to: string } {
  return { date_from: formatDate(dateFrom), date_to: formatDate(dateTo) };
}

function startOfDay(value: Date): Date {
  return new Date(value.getFullYear(), value.getMonth(), value.getDate());
}

function startOfWeek(value: Date): Date {
  const day = value.getDay();
  const offset = day === 0 ? -6 : 1 - day;
  return addDays(value, offset);
}

function addDays(value: Date, amount: number): Date {
  return new Date(value.getFullYear(), value.getMonth(), value.getDate() + amount);
}

function formatDate(value: Date): string {
  const month = String(value.getMonth() + 1).padStart(2, '0');
  const day = String(value.getDate()).padStart(2, '0');

  return `${value.getFullYear()}-${month}-${day}`;
}
