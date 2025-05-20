"use client"

import type React from "react"

import { useState, useRef } from "react"
import { Button } from "@/components/ui/button"
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import { ImageIcon, X } from "lucide-react"

interface FileUploadProps {
  onFileSelected: (file: File) => void
  onFileRemoved?: () => void
  defaultPreview?: string | null
  avatarSize?: "sm" | "md" | "lg" | "xl"
  label?: string
  accept?: string
}

export function FileUpload({
  onFileSelected,
  onFileRemoved,
  defaultPreview = null,
  avatarSize = "md",
  label = "Upload image",
  accept = "image/*",
}: FileUploadProps) {
  const [preview, setPreview] = useState<string | null>(defaultPreview)
  const fileInputRef = useRef<HTMLInputElement>(null)

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0]
    if (!file) return

    // Create preview URL
    const fileUrl = URL.createObjectURL(file)
    setPreview(fileUrl)
    onFileSelected(file)
  }

  const handleRemove = () => {
    if (preview && preview !== defaultPreview) {
      URL.revokeObjectURL(preview)
    }
    setPreview(defaultPreview)
    if (fileInputRef.current) {
      fileInputRef.current.value = ""
    }
    if (onFileRemoved) {
      onFileRemoved()
    }
  }

  // Determine avatar size class
  const sizeClass = {
    sm: "h-12 w-12",
    md: "h-16 w-16",
    lg: "h-24 w-24",
    xl: "h-32 w-32",
  }[avatarSize]

  return (
    <div className="flex flex-col items-center gap-2">
      <div className="relative">
        <Avatar className={`${sizeClass} border-2 border-muted`}>
          <AvatarImage src={preview || "/placeholder.svg"} />
          <AvatarFallback>
            <ImageIcon className="h-1/2 w-1/2 text-muted-foreground" />
          </AvatarFallback>
        </Avatar>
        {preview && (
          <Button
            type="button"
            variant="destructive"
            size="icon"
            className="absolute -top-2 -right-2 h-6 w-6 rounded-full"
            onClick={handleRemove}
          >
            <X className="h-3 w-3" />
          </Button>
        )}
      </div>
      <input type="file" ref={fileInputRef} className="hidden" accept={accept} onChange={handleFileChange} />
      <Button type="button" variant="outline" size="sm" onClick={() => fileInputRef.current?.click()}>
        {label}
      </Button>
    </div>
  )
}
