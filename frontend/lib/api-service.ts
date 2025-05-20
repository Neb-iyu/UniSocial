import axios, {
  type AxiosError,
  type AxiosInstance,
  type AxiosRequestConfig,
  type AxiosResponse,
} from "axios";

interface ApiResponse<T = any> {
  data: T;
  message?: string;
  status: string;
}

interface ApiError {
  message: string;
  errors?: Record<string, string[]>;
  status?: string;
  statusCode?: number;
}

interface LoginCredentials {
  login: string;
  password: string;
}

interface RegisterData {
  username: string;
  email: string;
  password: string;
  password_confirmation: string;
}

interface ProfileUpdateData {
  fullname?: string;
  bio?: string;
  location?: string;
  website?: string;
}

interface CreatePostData {
  content: string;
}

interface CreateCommentData {
  post_uuid: string;
  content: string;
}

interface PaginationParams {
  page?: number;
  limit?: number;
}

interface SearchParams extends PaginationParams {
  query: string;
}

class ApiService {
  private api: AxiosInstance;
  private token: string | null = null;

  constructor() {
    const baseURL = "https://unifyze.cloudet.co";

    if (typeof window !== "undefined") {
      this.token = localStorage.getItem("auth_token");
    }

    this.api = axios.create({
      baseURL,
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      withCredentials: false,
      timeout: 10000,
    });

    this.api.interceptors.request.use(
      (config) => {
        const currentToken = localStorage.getItem("auth_token") || this.token;
        if (currentToken) {
          config.headers.Authorization = `Bearer ${currentToken}`;
          this.token = currentToken;
        }
        return config;
      },
      (error) => Promise.reject(error)
    );

    this.api.interceptors.response.use(
      (response: AxiosResponse) => {
        return response.data || response;
      },
      (error: AxiosError<ApiError>) => {
        let errorMessage = "An unexpected error occurred";
        let statusCode = 500;

        if (error.response) {
          statusCode = error.response.status;
          const data = error.response.data;

          if (data?.message) {
            errorMessage = data.message;
          } else if (data?.errors) {
            const firstError = Object.values(data.errors)[0];
            errorMessage = Array.isArray(firstError)
              ? firstError[0]
              : String(firstError);
          }

          if (statusCode === 401) {
            this.clearToken();
            if (
              typeof window !== "undefined" &&
              !window.location.pathname.includes("/login") &&
              !window.location.pathname.includes("/register")
            ) {
              window.location.href = "/login";
            }
          }
        } else if (error.request) {
          errorMessage =
            "No response received from server. Please check your internet connection.";
        } else {
          errorMessage = error.message || errorMessage;
        }

        const enhancedError = new Error(errorMessage) as Error & {
          statusCode: number;
          originalError: AxiosError;
        };
        enhancedError.statusCode = statusCode;
        enhancedError.originalError = error;

        return Promise.reject(enhancedError);
      }
    );
  }

  async login(credentials: LoginCredentials) {
    const response = await this.api.post("/login", credentials);
    console.log(response);
    if (response.data?.token) {
      this.setToken(response.data.token);
    }
    return response;
  }

  async register(data: RegisterData) {
    const response = await this.api.post("/register", data);
    if (response.data?.token) {
      this.setToken(response.data.token);
    }
    return response;
  }

  async logout() {
    this.clearToken();
    return { success: true };
  }

  async getCurrentUser() {
    return this.api.get("/me");
  }

  async requestPasswordReset(email: string) {
    return this.api.post("/password-reset/request", { email });
  }

  async verifyResetCode(
    email: string,
    code: string,
    password: string,
    password_confirmation: string
  ) {
    return this.api.post("/password-reset/verify", {
      email,
      code,
      password,
      password_confirmation,
    });
  }

  async getUser(uuid: string) {
    return this.api.get(`/users/${uuid}`);
  }

  async getUserByUsername(username: string) {
    return this.api.get(`/me`);
  }

  async updateProfile(uuid: string, data: ProfileUpdateData) {
    return this.api.patch(`/users/${uuid}`, data);
  }

  async uploadProfilePicture(uuid: string, file: File) {
    const formData = new FormData();
    formData.append("profile_picture", file);

    return this.api.post(`/users/${uuid}/profile-picture`, formData, {
      headers: {
        "Content-Type": "multipart/form-data",
      },
    });
  }

  async getFollowers(uuid: string, params?: PaginationParams) {
    return this.api.get(`/users/${uuid}/followers`, { params });
  }

  async getFollowing(uuid: string, params?: PaginationParams) {
    return this.api.get(`/users/${uuid}/following`, { params });
  }

  async followUser(uuid: string) {
    return this.api.post(`/users/${uuid}/follow`);
  }

  async unfollowUser(uuid: string) {
    return this.api.delete(`/users/${uuid}/follow`);
  }

  async searchUsers(params: SearchParams) {
    return this.api.get("/users", { params });
  }

  async getFeed(params?: PaginationParams) {
    return this.api.get("/feed", { params });
  }

  async getPost(uuid: string) {
    return this.api.get(`/posts/${uuid}`);
  }

  async getUserPosts(uuid: string, params?: PaginationParams) {
    return this.api.get(`/users/${uuid}/posts`, { params });
  }

  async createPost(data: CreatePostData) {
    return this.api.post("/posts", data);
  }

  async updatePost(uuid: string, content: string) {
    return this.api.patch(`/posts/${uuid}`, { content });
  }

  async deletePost(uuid: string) {
    return this.api.delete(`/posts/${uuid}`);
  }

  async togglePostLike(uuid: string) {
    const cur = this.api.post(`/posts/${uuid}/like`);
    console.log(cur);
    return cur;
  }

  async getPostLikes(uuid: string, params?: PaginationParams) {
    return this.api.get(`/posts/${uuid}/likes`, { params });
  }

  async getComments(params: { post_uuid: string } & PaginationParams) {
    return this.api.get(`/posts/${params.post_uuid}/comments`, {
      params: { page: params.page, limit: params.limit },
    });
  }

  async createComment(data: CreateCommentData) {
    return this.api.post(`/posts/${data.post_uuid}/comments`, {
      content: data.content,
    });
  }

  async updateComment(uuid: string, content: string) {
    return this.api.patch(`/comments/${uuid}`, { content });
  }

  async deleteComment(uuid: string) {
    return this.api.delete(`/comments/${uuid}`);
  }

  async toggleCommentLike(uuid: string) {
    return this.api.post(`/comments/${uuid}/like`);
  }

  async getNotifications(params?: PaginationParams) {
    return this.api.get("/notifications", { params });
  }

  async markNotificationAsRead(uuid: string) {
    return this.api.patch(`/notifications/${uuid}`, { is_read: true });
  }

  async markAllNotificationsAsRead() {
    return this.api.post("/notifications/mark-all-read");
  }

  async get<T = any>(url: string, config?: AxiosRequestConfig): Promise<T> {
    return this.api.get(url, config);
  }

  async post<T = any>(
    url: string,
    data?: any,
    config?: AxiosRequestConfig
  ): Promise<T> {
    return this.api.post(url, data, config);
  }

  async put<T = any>(
    url: string,
    data?: any,
    config?: AxiosRequestConfig
  ): Promise<T> {
    return this.api.put(url, data, config);
  }

  async patch<T = any>(
    url: string,
    data?: any,
    config?: AxiosRequestConfig
  ): Promise<T> {
    return this.api.patch(url, data, config);
  }

  async delete<T = any>(url: string, config?: AxiosRequestConfig): Promise<T> {
    return this.api.delete(url, config);
  }

  setToken(token: string) {
    this.token = token;
    if (typeof window !== "undefined") {
      localStorage.setItem("auth_token", token);
    }
    this.api.defaults.headers.common.Authorization = `Bearer ${token}`;
  }

  clearToken() {
    this.token = null;
    if (typeof window !== "undefined") {
      localStorage.removeItem("auth_token");
    }
    delete this.api.defaults.headers.common.Authorization;
  }

  getToken(): string | null {
    return this.token;
  }

  isAuthenticated(): boolean {
    return !!this.token;
  }
}

export const apiService = new ApiService();
