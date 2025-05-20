"use client"

import { useState, useEffect } from "react"
import { useTheme } from "next-themes"
import { useToast } from "@/components/ui/use-toast"

export default function AppearanceSettingsPage() {
  const { theme, setTheme } = useTheme()
  const [colorTheme, setColorTheme] = useState("blue")
  const { toast } = useToast()
  const [mounted, setMounted] = useState(false)

  useEffect(() => {
    setMounted(true)
    // Get the current color theme from localStorage or default to blue
    const savedColorTheme = localStorage.getItem("colorTheme") || "blue"
    setColorTheme(savedColorTheme)
    
    // Apply the theme class to the document
    document.documentElement.classList.remove("theme-green", "theme-purple", "theme-orange")
    if (savedColorTheme !== "blue") {
      document.documentElement.classList.add(`theme-${savedColorTheme}`)
    }
  }, [])

// Avoid hydration mism
