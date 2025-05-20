"use client"

import type React from "react"

import { useState, useEffect } from "react"
import { useAuth } from "@/context/auth-context"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Textarea } from "@/components/ui/textarea"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { AlertCircle, Loader2, Check } from "lucide-react"
import { apiService } from "@/lib/api-service"
import { useToast } from "@/components/ui/use-toast"
import { FileUpload } from "@/components/file-upload"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"

export default function ProfileSettingsPage() {
  const { user, updateUserData } = useAuth()
  const [formData, setFormData] = useState({
    fullname: "",
    bio: "",
    university_id: "",
    year_of_study: "",
    gender: "",
  })
  const [profilePicture, setProfilePicture] = useState<File | null>(null)
  const [error, setError] = useState("")
  const [isLoading, setIsLoading] = useState(false)
  const { toast } = useToast()

  useEffect(() => {
    if (user) {
      setFormData({
        fullname: user.fullname || "",
        bio: user.bio || "",
        university_id: user.university_id || "",
        year_of_study: user.year_of_study?.toString() || "",
        gender: user.gender || "",
      })
    }
  }, [user])

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target
    setFormData((prev) => ({ ...prev, [name]: value }))
  }

  const handleSelectChange = (name: string, value: string) => {
    setFormData((prev) => ({ ...prev, [name]: value }))
  }

  const handleFileSelected = (file: File) => {
    setProfilePicture(file)
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setError("")
    setIsLoading(true)

    try {
      let response

      if (profilePicture) {
        // Create FormData for file upload
        const formData = new FormData()
        Object.entries(formData).forEach(([key, value]) => {
          formData.append(key, value)
        })
        formData.append("profile_picture", profilePicture)

        response = await apiService.patch(`/users/${user?.public_uuid}`, formData)
      } else {
        // Regular update without file
        response = await apiService.patch(`/users/${user?.public_uuid}`, formData)
      }

      updateUserData(response)
      toast({
        title: "Profile updated",
        description: "Your profile has been updated successfully.",
      })
    } catch (err: any) {
      setError(err.message || "Failed to update profile. Please try again.")
    } finally {
      setIsLoading(false)
    }
  }

  return (
    <div className="space-y-6">
      <div>
        <h3 className="text-lg font-medium">Profile</h3>
        <p className="text-sm text-muted-foreground">
          Update your personal information and how others see you on the platform.
        </p>
      </div>

      {error && (
        <Alert variant="destructive">
          <AlertCircle className="h-4 w-4" />
          <AlertDescription>{error}</AlertDescription>
        </Alert>
      )}

      <form onSubmit={handleSubmit} className="space-y-6">
        <div className="space-y-2">
          <Label>Profile Picture</Label>
          <div className="flex items-center gap-4">
            <FileUpload
              onFileSelected={handleFileSelected}
              defaultPreview={user?.profile_picture_url}
              avatarSize="md"
              label="Change picture"
            />
            <div className="flex-1">
              <p className="text-sm text-muted-foreground">
                Upload a new profile picture. Recommended size: 400x400 pixels.
              </p>
            </div>
          </div>
        </div>

        <div className="space-y-2">
          <Label htmlFor="fullname">Full Name</Label>
          <Input
            id="fullname"
            name="fullname"
            placeholder="Your full name"
            value={formData.fullname}
            onChange={handleChange}
            required
          />
        </div>

        <div className="space-y-2">
          <Label htmlFor="bio">Bio</Label>
          <Textarea
            id="bio"
            name="bio"
            placeholder="Tell us about yourself"
            value={formData.bio}
            onChange={handleChange}
            rows={4}
          />
        </div>

        <div className="space-y-2">
          <Label htmlFor="university_id">University ID</Label>
          <Input
            id="university_id"
            name="university_id"
            placeholder="Your university ID"
            value={formData.university_id}
            onChange={handleChange}
          />
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div className="space-y-2">
            <Label htmlFor="year_of_study">Year of Study</Label>
            <Select
              value={formData.year_of_study}
              onValueChange={(value) => handleSelectChange("year_of_study", value)}
            >
              <SelectTrigger>
                <SelectValue placeholder="Select year" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="not_specified">Not specified</SelectItem>
                <SelectItem value="1">First Year</SelectItem>
                <SelectItem value="2">Second Year</SelectItem>
                <SelectItem value="3">Third Year</SelectItem>
                <SelectItem value="4">Fourth Year</SelectItem>
                <SelectItem value="5">Fifth Year</SelectItem>
                <SelectItem value="6">Sixth Year</SelectItem>
                <SelectItem value="7">Graduate</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div className="space-y-2">
            <Label htmlFor="gender">Gender</Label>
            <Select value={formData.gender} onValueChange={(value) => handleSelectChange("gender", value)}>
              <SelectTrigger>
                <SelectValue placeholder="Select gender" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="not_specified">Not specified</SelectItem>
                <SelectItem value="male">Male</SelectItem>
                <SelectItem value="female">Female</SelectItem>
                <SelectItem value="prefer_not_to_say">Prefer not to say</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>

        <Button type="submit" disabled={isLoading}>
          {isLoading ? (
            <>
              <Loader2 className="mr-2 h-4 w-4 animate-spin" />
              Saving...
            </>
          ) : (
            <>
              <Check className="mr-2 h-4 w-4" />
              Save changes
            </>
          )}
        </Button>
      </form>
    </div>
  )
}
