"use client"

import type React from "react"

import { useState, useEffect } from "react"
import { useRouter } from "next/navigation"
import { useAuth } from "@/context/auth-context"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Textarea } from "@/components/ui/textarea"
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { AlertCircle, Loader2, ArrowLeft } from "lucide-react"
import { FileUpload } from "@/components/file-upload"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"

export default function ProfileSetupPage() {
  const [formData, setFormData] = useState({
    bio: "",
    university_id: "",
    year_of_study: "",
    gender: "",
  })
  const [profilePicture, setProfilePicture] = useState<File | null>(null)
  const [error, setError] = useState("")
  const [isLoading, setIsLoading] = useState(false)
  const { completeRegistration } = useAuth()
  const router = useRouter()

  // Redirect to login if no registration data is in progress
  useEffect(() => {
    // This would be a more robust check in a real app
    // For now, we'll just let the user stay on this page
  }, [])

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

  const handleFileRemoved = () => {
    setProfilePicture(null)
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setError("")
    setIsLoading(true)

    try {
      await completeRegistration({
        ...formData,
        profile_picture: profilePicture,
      })

      // Redirect to feed after successful registration
      router.push("/feed")
    } catch (err: any) {
      setError(err.message || "Profile setup failed. Please try again.")
    } finally {
      setIsLoading(false)
    }
  }

  const handleSkip = async () => {
    setIsLoading(true)
    try {
      await completeRegistration({})
      router.push("/feed")
    } catch (err: any) {
      setError(err.message || "Registration failed. Please try again.")
    } finally {
      setIsLoading(false)
    }
  }

  return (
    <div className="flex justify-center items-center min-h-screen p-4">
      <Card className="w-full max-w-md">
        <CardHeader className="space-y-1">
          <CardTitle className="text-2xl font-bold text-center">Complete your profile</CardTitle>
          <CardDescription className="text-center">
            Add more details to help others find and connect with you
          </CardDescription>
        </CardHeader>
        <CardContent>
          {error && (
            <Alert variant="destructive" className="mb-4">
              <AlertCircle className="h-4 w-4" />
              <AlertDescription>{error}</AlertDescription>
            </Alert>
          )}
          <form onSubmit={handleSubmit} className="space-y-4">
            <div className="flex justify-center mb-4">
              <FileUpload
                onFileSelected={handleFileSelected}
                onFileRemoved={handleFileRemoved}
                avatarSize="lg"
                label="Upload profile picture"
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
                rows={3}
              />
              <p className="text-xs text-muted-foreground">
                Share a bit about yourself, your interests, or what you're studying
              </p>
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

            <div className="flex flex-col sm:flex-row gap-2 pt-2">
              <Button type="button" variant="outline" className="flex-1" onClick={handleSkip} disabled={isLoading}>
                Skip for now
              </Button>
              <Button type="submit" className="flex-1" disabled={isLoading}>
                {isLoading ? (
                  <>
                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                    Completing setup...
                  </>
                ) : (
                  "Complete setup"
                )}
              </Button>
            </div>
          </form>
        </CardContent>
        <CardFooter className="flex justify-center">
          <Button variant="link" onClick={() => router.back()} disabled={isLoading}>
            <ArrowLeft className="h-4 w-4 mr-2" />
            Back to previous step
          </Button>
        </CardFooter>
      </Card>
    </div>
  )
}
