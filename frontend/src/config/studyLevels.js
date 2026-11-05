/** Mirrors App\Enums\StudyLevel (system values; labels are UI text). */
export const STUDY_LEVELS = [
  { value: 'foundation', label: 'Foundation' },
  { value: 'diploma', label: 'Diploma / Top-up' },
  { value: 'undergraduate', label: 'Undergraduate' },
  { value: 'postgraduate', label: 'Postgraduate' },
  { value: 'doctorate', label: 'PhD / Doctorate' },
  { value: 'language', label: 'Language Course' },
]

export const studyLevelLabel = (value) => STUDY_LEVELS.find((l) => l.value === value)?.label ?? value
