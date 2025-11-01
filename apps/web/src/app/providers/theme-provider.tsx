/* eslint-disable react-refresh/only-export-components */
import React from 'react'

const ThemeContext = React.createContext({
  toggle: () => {},
})

export function ThemeProvider({ children }: { children: React.ReactNode }) {
  const [isDark, setIsDark] = React.useState(() =>
    typeof window !== 'undefined' ? document.documentElement.classList.contains('dark') : false
  )

  React.useEffect(() => {
    document.documentElement.classList.toggle('dark', isDark)
  }, [isDark])

  const toggle = React.useCallback(() => setIsDark((prev) => !prev), [])

  return <ThemeContext.Provider value={{ toggle }}>{children}</ThemeContext.Provider>
}

export function useTheme() {
  return React.useContext(ThemeContext)
}
