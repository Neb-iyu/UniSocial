"use client";

import type React from "react";
import { createContext, useContext, useState, useEffect } from "react";
import { apiService } from "@/lib/api-service";
import type { User } from "@/types/user";
import { useToast } from "@/components/ui/use-toast";

interface AuthContextType {
  user: User | null;
  token: string | null;
  isLoading: boolean;
  isAuthenticated: boolean;
  login: (credentials: { login: string; password: string }) => Promise<void>;
  register: (userData: RegisterData) => Promise<void>;
  completeRegistration: (profileData: ProfileData) => Promise<void>;
  logout: () => void;
  updateUserData: (userData: Partial<User>) => void;
}

interface RegisterData {
  email: string;
  password: string;
  username: string;
  fullname: string;
}

interface ProfileData {
  bio?: string;
  university_id?: string;
  year_of_study?: string;
  gender?: string;
  profile_picture?: File | null;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [token, setToken] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [registrationData, setRegistrationData] = useState<RegisterData | null>(
    null
  );
  const { toast } = useToast();

  useEffect(() => {
    // Check for token in localStorage
    const storedToken = localStorage.getItem("auth_token");
    if (storedToken) {
      setToken(storedToken);
      apiService.setToken(storedToken);
      fetchCurrentUser();
    } else {
      setIsLoading(false);
    }
  }, []);

  const fetchCurrentUser = async () => {
    try {
      const response = await apiService.getCurrentUser();
      const userData = response.data as User;
      if (
        !userData ||
        typeof userData !== "object" ||
        !("username" in userData)
      ) {
        throw new Error("Invalid user data received");
      }
      if (userData && typeof userData === "object" && "username" in userData) {
        const validatedUser: User = {
          public_uuid: userData.public_uuid as string,
          email: userData.email as string,
          fullname: userData.fullname as string,
          username: userData.username as string,
          created_at: userData.created_at as string,
          updated_at: userData.updated_at as string,
        };
        setUser(validatedUser);
      } else {
        throw new Error("Invalid user data received");
      }
    } catch (error: any) {
      console.error("Failed to fetch user:", error);
      toast({
        title: "Authentication Error",
        description:
          error.message || "Failed to authenticate. Please log in again.",
        variant: "destructive",
      });
      logout();
    } finally {
      setIsLoading(false);
    }
  };

  // auth-context.tsx
  const login = async (credentials: { login: string; password: string }) => {
    setIsLoading(true);
    try {
      const response = await apiService.login(credentials);
      const {
        token: newToken,
        user: userData,
        role,
      } = response.data as { token: string; user: User; role?: string };

      // Save token and role to localStorage and cookies
      localStorage.setItem("auth_token", newToken);
      document.cookie = `auth_token=${newToken}; path=/; max-age=${
        60 * 60 * 24 * 7
      }`;
      if (role) {
        localStorage.setItem("user_role", role);
        document.cookie = `user_role=${role}; path=/; max-age=${
          60 * 60 * 24 * 7
        }`;
      }
      setToken(newToken);
      setUser(userData);
      apiService.setToken(newToken);

      toast({
        title: "Login Successful",
        description: `Welcome back!`,
      });
    } catch (error: any) {
      console.error("Login failed:", error);
      toast({
        title: "Login Failed",
        description: error.message || "Invalid credentials. Please try again.",
        variant: "destructive",
      });
      throw new Error(
        error.message || "Login failed. Please check your credentials."
      );
    } finally {
      setIsLoading(false);
    }
  };

  const register = async (userData: RegisterData) => {
    setIsLoading(true);
    try {
      // Store registration data for the second step
      setRegistrationData(userData);

      toast({
        title: "Registration Started",
        description: "Please complete your profile in the next step.",
      });
    } catch (error: any) {
      console.error("Registration failed:", error);
      toast({
        title: "Registration Failed",
        description: error.message || "Registration failed. Please try again.",
        variant: "destructive",
      });
      throw new Error(
        error.message || "Registration failed. Please try again."
      );
    } finally {
      setIsLoading(false);
    }
  };

  const completeRegistration = async (profileData: ProfileData) => {
    if (!registrationData) {
      toast({
        title: "Registration Error",
        description:
          "Registration data is missing. Please start from the beginning.",
        variant: "destructive",
      });
      throw new Error(
        "Registration data is missing. Please start from the beginning."
      );
    }

    setIsLoading(true);
    try {
      // Create FormData if there's a profile picture
      let data: any = { ...registrationData };

      if (profileData.profile_picture) {
        const formData = new FormData();
        Object.entries(registrationData).forEach(([key, value]) => {
          formData.append(key, value);
        });

        // Add profile data
        if (profileData.bio) formData.append("bio", profileData.bio);
        if (profileData.university_id)
          formData.append("university_id", profileData.university_id);
        if (profileData.year_of_study)
          formData.append("year_of_study", profileData.year_of_study);
        if (profileData.gender) formData.append("gender", profileData.gender);
        if (profileData.profile_picture)
          formData.append("profile_picture", profileData.profile_picture);

        data = formData;
      } else {
        // Combine registration data with profile data
        data = {
          ...registrationData,
          ...profileData,
        };
      }

      // Register with all data
      const response = await apiService.register(data);
      const { token: newToken, user: newUser } = response.data as {
        token: string;
        user: User;
      };

      // Save token and user data
      localStorage.setItem("auth_token", newToken);
      setToken(newToken);
      setUser(newUser);
      apiService.setToken(newToken);

      // Clear registration data
      setRegistrationData(null);

      toast({
        title: "Registration Successful",
        description: `Welcome to Unifyze, ${newUser.username}!`,
      });
    } catch (error: any) {
      console.error("Profile setup failed:", error);
      toast({
        title: "Registration Failed",
        description: error.message || "Profile setup failed. Please try again.",
        variant: "destructive",
      });
      throw new Error(
        error.message || "Profile setup failed. Please try again."
      );
    } finally {
      setIsLoading(false);
    }
  };

  const logout = () => {
    localStorage.removeItem("auth_token");
    setToken(null);
    setUser(null);
    apiService.clearToken();

    toast({
      title: "Logged Out",
      description: "You have been successfully logged out.",
    });
  };

  const updateUserData = (userData: Partial<User>) => {
    if (user) {
      setUser({ ...user, ...userData });
    }
  };

  const value = {
    user,
    token,
    isLoading,
    isAuthenticated: !!token && !!user,
    login,
    register,
    completeRegistration,
    logout,
    updateUserData,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error("useAuth must be used within an AuthProvider");
  }
  return context;
};
