import { NextResponse } from "next/server"
import type { NextRequest } from "next/server"

// Routes that don't require authentication
const publicRoutes = ["/", "/login", "/register", "/forgot-password", "/reset-password"]

// Routes that require admin privileges
const adminRoutes = ["/admin"]

export function middleware(request: NextRequest) {
  const { pathname } = request.nextUrl;
  const token = request.cookies.get("auth_token")?.value;
  console.log("Middleware: Pathname =", pathname, "Token =", token);

  const isPublicRoute = publicRoutes.some(
    (route) => pathname === route || pathname.startsWith(`${route}/`)
  );

  if (isPublicRoute) {
    console.log("Middleware: Allowing public route");
    return NextResponse.next();
  }

  if (!token) {
    console.log("Middleware: No token, redirecting to /login");
    const url = new URL("/login", request.url);
    url.searchParams.set("callbackUrl", encodeURI(request.url));
    return NextResponse.redirect(url);
  }

  const isAdminRoute = adminRoutes.some(
    (route) => pathname === route || pathname.startsWith(`${route}/`)
  );
  const userRole = request.cookies.get("user_role")?.value;

  if (isAdminRoute && userRole !== "admin" && userRole !== "superadmin") {
    console.log("Middleware: Non-admin user, redirecting to /");
    return NextResponse.redirect(new URL("/", request.url));
  }

  console.log("Middleware: Allowing authenticated route");
  return NextResponse.next();
}

export const config = {
  matcher: [
    /*
     * Match all request paths except:
     * - _next/static (static files)
     * - _next/image (image optimization files)
     * - favicon.ico (favicon file)
     * - public folder
     * - api routes
     */
    "/((?!_next/static|_next/image|favicon.ico|public|api).*)",
  ],
}
