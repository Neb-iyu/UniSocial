"use client";

import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { useToast } from "@/components/ui/use-toast";
import { apiService } from "@/lib/api-service";
import { ImageIcon, X } from "lucide-react";

interface CreatePostFormProps {
  onPostCreated?: (post: any) => void;
}

const CreatePostForm: React.FC<CreatePostFormProps> = ({ onPostCreated }) => {
  const [content, setContent] = useState("");
  const [selectedFiles, setSelectedFiles] = useState<File[]>([]);
  const [previewUrls, setPreviewUrls] = useState<string[]>([]);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const { toast } = useToast();

  const handleFileSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files.length > 0) {
      const files = Array.from(e.target.files);
      const newFiles = [...selectedFiles, ...files].slice(0, 4); // Limit to 4 files

      if (newFiles.length !== selectedFiles.length) {
        const newPreviewUrls = files.map((file) => URL.createObjectURL(file));
        setSelectedFiles(newFiles);
        setPreviewUrls([...previewUrls, ...newPreviewUrls].slice(0, 4));
      }
    }
  };

  const handleRemoveFile = (index: number) => {
    const newSelectedFiles = [...selectedFiles];
    newSelectedFiles.splice(index, 1);
    setSelectedFiles(newSelectedFiles);

    const newPreviewUrls = [...previewUrls];
    URL.revokeObjectURL(newPreviewUrls[index]);
    newPreviewUrls.splice(index, 1);
    setPreviewUrls(newPreviewUrls);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!content.trim() && selectedFiles.length === 0) {
      toast({
        title: "Error",
        description: "Please enter some content or attach media",
        variant: "destructive",
      });
      return;
    }

    setIsSubmitting(true);
    try {
      const formData = new FormData();
      formData.append("content", content);
      selectedFiles.forEach((file) => {
        formData.append("media_files[]", file); // Changed to match API expectation
      });

      const response = await apiService.createPost(formData);

      setContent("");
      setSelectedFiles([]);
      setPreviewUrls([]);

      if (onPostCreated) {
        onPostCreated(response);
      }

      toast({
        title: "Success",
        description: "Your post has been published",
      });
    } catch (error: any) {
      console.error("Failed to create post:", error);
      toast({
        title: "Error",
        description:
          error.message || "Failed to create post. Please try again.",
        variant: "destructive",
      });
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <div>
        <Textarea
          value={content}
          onChange={(e) => setContent(e.target.value)}
          placeholder="What's on your mind?"
          rows={4}
          className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
        />
      </div>

      {previewUrls.length > 0 && (
        <div className="flex flex-wrap gap-2">
          {previewUrls.map((url, index) => (
            <div key={index} className="relative">
              <img
                src={url}
                alt={`Preview ${index + 1}`}
                className="h-24 w-24 rounded-md object-cover"
              />
              <Button
                type="button"
                variant="ghost"
                size="icon"
                className="absolute -top-2 -right-2 h-6 w-6 rounded-full bg-background shadow"
                onClick={() => handleRemoveFile(index)}
              >
                <X className="h-4 w-4" />
                <span className="sr-only">Remove</span>
              </Button>
            </div>
          ))}
        </div>
      )}

      <div className="flex items-center justify-between">
        <div>
          <Input
            type="file"
            id="media"
            multiple
            accept="image/*,video/*"
            className="hidden"
            onChange={handleFileSelect}
            disabled={selectedFiles.length >= 4}
          />
          <label
            htmlFor="media"
            className={`inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2 ${
              selectedFiles.length >= 4 ? "opacity-50 cursor-not-allowed" : ""
            }`}
          >
            <ImageIcon className="mr-2 h-4 w-4" />
            <span>Add Media ({selectedFiles.length}/4)</span>
          </label>
        </div>
        <Button type="submit" disabled={isSubmitting}>
          {isSubmitting ? "Publishing..." : "Publish"}
        </Button>
      </div>
    </form>
  );
};

export default CreatePostForm;
