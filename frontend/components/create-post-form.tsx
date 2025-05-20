"use client";

import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";
import { useToast } from "@/components/ui/use-toast";
import { apiService } from "@/lib/api-service";

interface Post {
  id: string;
  content: string;
  created_at: string;
  user: any; // Replace with your User type from auth-context
}

interface CreatePostFormProps {
  onPostCreated?: (post: Post) => void;
}

const CreatePostForm: React.FC<CreatePostFormProps> = ({ onPostCreated }) => {
  const [content, setContent] = useState("");
  const [isSubmitting, setIsSubmitting] = useState(false);
  const { toast } = useToast();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!content.trim()) {
      toast({
        title: "Error",
        description: "Please enter some content",
        variant: "destructive",
      });
      return;
    }

    setIsSubmitting(true);
    try {
      const response = await apiService.createPost({ content });
      console.log("Create post response:", response);

      setContent("");

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
          maxLength={280} // Optional: Added for platforms with post length limits
          className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
        />
      </div>
      <div className="flex justify-end">
        <Button type="submit" disabled={isSubmitting}>
          {isSubmitting ? "Publishing..." : "Publish"}
        </Button>
      </div>
    </form>
  );
};

export default CreatePostForm;
