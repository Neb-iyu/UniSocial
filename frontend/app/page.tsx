"use client"

import Link from "next/link"
import { motion } from "framer-motion"
import { Button } from "@/components/ui/button"
import { ArrowRight, CheckCircle, Users, MessageSquare, Bell, Shield } from "lucide-react"
import { AnimatedGradientBackground } from "@/components/animated-gradient-background"
import { FeatureCard } from "@/components/feature-card"
import { TestimonialCard } from "@/components/testimonial-card"

export default function HomePage() {
  // Animation variants
  const containerVariants = {
    hidden: { opacity: 0 },
    visible: {
      opacity: 1,
      transition: {
        staggerChildren: 0.2,
        delayChildren: 0.3,
      },
    },
  }

  const itemVariants = {
    hidden: { y: 20, opacity: 0 },
    visible: {
      y: 0,
      opacity: 1,
      transition: { type: "spring", stiffness: 100 },
    },
  }

  const fadeIn = {
    hidden: { opacity: 0, y: 20 },
    visible: {
      opacity: 1,
      y: 0,
      transition: { duration: 0.6 },
    },
  }

  const staggerFeatures = {
    hidden: { opacity: 0 },
    visible: {
      opacity: 1,
      transition: {
        staggerChildren: 0.1,
        delayChildren: 0.3,
      },
    },
  }

  // Features data
  const features = [
    {
      icon: Users,
      title: "Connect with peers",
      description: "Find and connect with students from your university, courses, and interests.",
    },
    {
      icon: MessageSquare,
      title: "Engage in discussions",
      description: "Share your thoughts, ask questions, and participate in meaningful conversations.",
    },
    {
      icon: Bell,
      title: "Stay updated",
      description: "Get notifications about activities relevant to your interests and connections.",
    },
    {
      icon: Shield,
      title: "Safe environment",
      description: "Enjoy a platform designed with privacy and safety as top priorities.",
    },
    {
      icon: CheckCircle,
      title: "Verified university accounts",
      description: "Connect with confidence knowing users are verified university students.",
    },
    {
      icon: ArrowRight,
      title: "Career opportunities",
      description: "Discover internships, job openings, and networking events exclusive to your university.",
    },
  ]

  // Testimonials data
  const testimonials = [
    {
      quote:
        "Unifyze helped me connect with classmates in my major that I never would have met otherwise. It's been invaluable for study groups!",
      name: "Alex Johnson",
      role: "Computer Science, Stanford University",
    },
    {
      quote:
        "The platform is so intuitive and focused on academic connections. I've found research partners and made friends across different departments.",
      name: "Priya Patel",
      role: "Biochemistry, MIT",
    },
    {
      quote:
        "As an international student, Unifyze made it so much easier to integrate into campus life and find people with similar interests.",
      name: "Miguel Sanchez",
      role: "Business Administration, NYU",
    },
  ]

  return (
    <div className="flex flex-col min-h-screen ml-12">
      <AnimatedGradientBackground />

      {/* Header */}
      <header className="border-b backdrop-blur-sm bg-background/80 sticky top-0 z-50">
        <div className="container flex h-16 items-center justify-between">
          <motion.div
            initial={{ opacity: 0, x: -20 }}
            animate={{ opacity: 1, x: 0 }}
            transition={{ duration: 0.5 }}
            className="flex items-center gap-6"
          >
            <Link
              href="/"
              className="font-bold text-2xl bg-clip-text text-transparent bg-gradient-to-r from-purple-600 to-blue-500"
            >
              Unifyze
            </Link>
          </motion.div>
          <motion.div
            initial={{ opacity: 0, x: 20 }}
            animate={{ opacity: 1, x: 0 }}
            transition={{ duration: 0.5 }}
            className="flex items-center gap-4"
          >
            <Link href="/login">
              <Button variant="ghost" className="hover:bg-purple-500/10 hover:text-purple-500 transition-all">
                Login
              </Button>
            </Link>
            <Link href="/register">
              <Button className="bg-gradient-to-r from-purple-600 to-blue-500 hover:from-purple-700 hover:to-blue-600 transition-all">
                Sign Up
              </Button>
            </Link>
          </motion.div>
        </div>
      </header>

      {/* Main Content */}
      <main className="flex-1">
        {/* Hero Section */}
        <section className="py-20 md:py-28 lg:py-36 overflow-hidden">
          <div className="container px-4 md:px-6">
            <div className="grid gap-12 lg:grid-cols-2 lg:gap-16 items-center">
              <motion.div initial="hidden" animate="visible" variants={containerVariants} className="space-y-6">
                <motion.div variants={itemVariants}>
                  <span className="inline-block px-3 py-1 rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-300 text-sm font-medium mb-4">
                    The University Social Network
                  </span>
                </motion.div>
                <motion.h1
                  variants={itemVariants}
                  className="text-4xl font-bold tracking-tighter sm:text-5xl md:text-6xl bg-clip-text text-transparent bg-gradient-to-r from-purple-600 to-blue-500"
                >
                  Connect with your university community
                </motion.h1>
                <motion.p variants={itemVariants} className="text-muted-foreground text-lg md:text-xl max-w-[600px]">
                  Unifyze is the social platform designed specifically for university students. Share ideas, connect
                  with peers, and build your network in a safe and engaging environment.
                </motion.p>
                <motion.div variants={itemVariants} className="flex flex-col gap-3 min-[400px]:flex-row">
                  <Link href="/register">
                    <Button
                      size="lg"
                      className="w-full min-[400px]:w-auto bg-gradient-to-r from-purple-600 to-blue-500 hover:from-purple-700 hover:to-blue-600 transition-all"
                    >
                      Get Started <ArrowRight className="ml-2 h-4 w-4" />
                    </Button>
                  </Link>
                  <Link href="/login">
                    <Button
                      size="lg"
                      variant="outline"
                      className="w-full min-[400px]:w-auto border-purple-200 dark:border-purple-800 hover:bg-purple-500/10 hover:text-purple-500 transition-all"
                    >
                      Login
                    </Button>
                  </Link>
                </motion.div>
                <motion.div variants={itemVariants} className="flex items-center gap-4 text-sm text-muted-foreground">
                  <div className="flex -space-x-2">
                    {[1, 2, 3, 4].map((i) => (
                      <div key={i} className="h-8 w-8 rounded-full border-2 border-background overflow-hidden">
                        <img
                          src={`/placeholder.svg?height=32&width=32&text=${i}`}
                          alt={`User ${i}`}
                          className="h-full w-full object-cover"
                        />
                      </div>
                    ))}
                  </div>
                  <span>
                    Join <span className="font-medium text-foreground">10,000+</span> students
                  </span>
                </motion.div>
              </motion.div>
              <motion.div
                initial={{ opacity: 0, scale: 0.9, rotate: 3 }}
                animate={{ opacity: 1, scale: 1, rotate: 0 }}
                transition={{ duration: 0.7, type: "spring" }}
                className="mx-auto lg:ml-auto relative"
              >
                <div className="absolute inset-0 bg-gradient-to-r from-purple-600/20 to-blue-500/20 blur-3xl rounded-full" />
                <div className="relative bg-gradient-to-r p-1 from-purple-600 to-blue-500 rounded-2xl shadow-xl">
                  <div className="bg-background dark:bg-background/95 rounded-xl overflow-hidden">
                    <img
                      alt="Unifyze App Screenshot"
                      className="w-full aspect-video object-cover object-center"
                      src="/placeholder.svg?height=600&width=800&text=Unifyze+Interface"
                    />
                  </div>
                </div>
                <div className="absolute -bottom-6 -right-6 h-24 w-24 bg-gradient-to-br from-purple-600 to-blue-500 rounded-full blur-xl opacity-50" />
                <div className="absolute -top-6 -left-6 h-24 w-24 bg-gradient-to-br from-purple-600 to-blue-500 rounded-full blur-xl opacity-50" />
              </motion.div>
            </div>
          </div>
        </section>

        {/* Features Section */}
        <motion.section
          initial="hidden"
          whileInView="visible"
          viewport={{ once: true, margin: "-100px" }}
          variants={fadeIn}
          className="py-20 bg-gradient-to-b from-muted/50 to-muted"
        >
          <div className="container px-4 md:px-6">
            <div className="text-center max-w-3xl mx-auto mb-16">
              <h2 className="text-3xl font-bold tracking-tighter md:text-4xl/tight mb-4">
                Everything you need to connect with your university community
              </h2>
              <p className="text-muted-foreground md:text-lg">
                Unifyze provides all the tools you need to engage with fellow students, share ideas, and build
                meaningful connections.
              </p>
            </div>

            <motion.div
              variants={staggerFeatures}
              initial="hidden"
              whileInView="visible"
              viewport={{ once: true, margin: "-100px" }}
              className="grid md:grid-cols-2 lg:grid-cols-3 gap-8"
            >
              {features.map((feature, index) => (
                <FeatureCard
                  key={index}
                  icon={feature.icon}
                  title={feature.title}
                  description={feature.description}
                  index={index}
                />
              ))}
            </motion.div>
          </div>
        </motion.section>

        {/* How It Works Section */}
        <motion.section
          initial="hidden"
          whileInView="visible"
          viewport={{ once: true, margin: "-100px" }}
          variants={fadeIn}
          className="py-20"
        >
          <div className="container px-4 md:px-6">
            <div className="text-center max-w-3xl mx-auto mb-16">
              <h2 className="text-3xl font-bold tracking-tighter md:text-4xl/tight mb-4">How Unifyze Works</h2>
              <p className="text-muted-foreground md:text-lg">
                Getting started with Unifyze is easy. Follow these simple steps to join your university community.
              </p>
            </div>

            <div className="grid md:grid-cols-3 gap-8 relative">
              <div className="hidden md:block absolute top-1/2 left-1/4 right-1/4 h-0.5 bg-gradient-to-r from-purple-600 to-blue-500" />

              {[
                {
                  step: "01",
                  title: "Create an account",
                  description: "Sign up with your university email to verify your student status.",
                },
                {
                  step: "02",
                  title: "Complete your profile",
                  description: "Add your interests, courses, and a profile picture to help others find you.",
                },
                {
                  step: "03",
                  title: "Connect and engage",
                  description: "Start following peers, joining discussions, and sharing content.",
                },
              ].map((item, index) => (
                <motion.div
                  key={index}
                  initial={{ opacity: 0, y: 20 }}
                  whileInView={{ opacity: 1, y: 0 }}
                  transition={{ delay: index * 0.2 }}
                  viewport={{ once: true }}
                  className="relative z-10"
                >
                  <div className="bg-background border rounded-xl p-6 h-full">
                    <div className="mb-4 text-5xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-purple-600 to-blue-500">
                      {item.step}
                    </div>
                    <h3 className="text-xl font-bold mb-2">{item.title}</h3>
                    <p className="text-muted-foreground">{item.description}</p>
                  </div>
                </motion.div>
              ))}
            </div>
          </div>
        </motion.section>

        {/* Testimonials Section */}
        <motion.section
          initial="hidden"
          whileInView="visible"
          viewport={{ once: true, margin: "-100px" }}
          variants={fadeIn}
          className="py-20 bg-muted"
        >
          <div className="container px-4 md:px-6">
            <div className="text-center max-w-3xl mx-auto mb-16">
              <h2 className="text-3xl font-bold tracking-tighter md:text-4xl/tight mb-4">What Students Say</h2>
              <p className="text-muted-foreground md:text-lg">
                Hear from students who have already joined the Unifyze community.
              </p>
            </div>

            <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
              {testimonials.map((testimonial, index) => (
                <TestimonialCard
                  key={index}
                  quote={testimonial.quote}
                  name={testimonial.name}
                  role={testimonial.role}
                  index={index}
                />
              ))}
            </div>
          </div>
        </motion.section>

        {/* Stats Section */}
        <motion.section
          initial="hidden"
          whileInView="visible"
          viewport={{ once: true, margin: "-100px" }}
          variants={fadeIn}
          className="py-20"
        >
          <div className="container px-4 md:px-6">
            <div className="grid md:grid-cols-4 gap-8 text-center">
              {[
                { value: "10,000+", label: "Active Students" },
                { value: "50+", label: "Universities" },
                { value: "1M+", label: "Posts Shared" },
                { value: "5M+", label: "Connections Made" },
              ].map((stat, index) => (
                <motion.div
                  key={index}
                  initial={{ opacity: 0, scale: 0.8 }}
                  whileInView={{ opacity: 1, scale: 1 }}
                  transition={{ delay: index * 0.1 }}
                  viewport={{ once: true }}
                  className="p-6 rounded-xl bg-background border"
                >
                  <div className="text-4xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-purple-600 to-blue-500 mb-2">
                    {stat.value}
                  </div>
                  <div className="text-muted-foreground">{stat.label}</div>
                </motion.div>
              ))}
            </div>
          </div>
        </motion.section>

        {/* FAQ Section */}
        <motion.section
          initial="hidden"
          whileInView="visible"
          viewport={{ once: true, margin: "-100px" }}
          variants={fadeIn}
          className="py-20 bg-gradient-to-b from-background to-muted/50"
        >
          <div className="container px-4 md:px-6">
            <div className="text-center max-w-3xl mx-auto mb-16">
              <h2 className="text-3xl font-bold tracking-tighter md:text-4xl/tight mb-4">Frequently Asked Questions</h2>
              <p className="text-muted-foreground md:text-lg">Find answers to common questions about Unifyze.</p>
            </div>

            <div className="max-w-3xl mx-auto space-y-4">
              {[
                {
                  question: "Who can join Unifyze?",
                  answer:
                    "Unifyze is exclusively for university students. You'll need a valid university email address to sign up and verify your account.",
                },
                {
                  question: "Is Unifyze free to use?",
                  answer:
                    "Yes, Unifyze is completely free for all university students. We believe in providing an accessible platform for all students to connect and collaborate.",
                },
                {
                  question: "How does Unifyze verify university students?",
                  answer:
                    "We verify students through their university email addresses. After signing up, you'll receive a verification email to confirm your student status.",
                },
                {
                  question: "Can I connect with students from other universities?",
                  answer:
                    "Yes! While Unifyze focuses on your own university community, you can also connect with students from other universities for networking and collaboration.",
                },
                {
                  question: "How is my data protected on Unifyze?",
                  answer:
                    "We take privacy seriously. Your data is encrypted and stored securely. We never share your personal information with third parties without your consent.",
                },
              ].map((faq, index) => (
                <motion.div
                  key={index}
                  initial={{ opacity: 0, y: 10 }}
                  whileInView={{ opacity: 1, y: 0 }}
                  transition={{ delay: index * 0.1 }}
                  viewport={{ once: true }}
                  whileHover={{
                    scale: 1.01,
                    transition: { duration: 0.2 },
                  }}
                  className="bg-background border rounded-xl p-6 hover:shadow-md transition-all"
                >
                  <h3 className="text-lg font-medium mb-2">{faq.question}</h3>
                  <p className="text-muted-foreground">{faq.answer}</p>
                </motion.div>
              ))}
            </div>
          </div>
        </motion.section>

        {/* CTA Section */}
        <motion.section
          initial="hidden"
          whileInView="visible"
          viewport={{ once: true, margin: "-100px" }}
          variants={fadeIn}
          className="py-20 bg-gradient-to-r from-purple-600/10 to-blue-500/10"
        >
          <div className="container px-4 md:px-6">
            <div className="max-w-3xl mx-auto text-center">
              <h2 className="text-3xl font-bold tracking-tighter md:text-4xl/tight mb-4">
                Ready to join your university community?
              </h2>
              <p className="text-muted-foreground md:text-lg mb-8">
                Sign up today and start connecting with fellow students, sharing ideas, and building your network.
              </p>
              <div className="flex flex-col sm:flex-row gap-4 justify-center">
                <Link href="/register">
                  <Button
                    size="lg"
                    className="w-full sm:w-auto bg-gradient-to-r from-purple-600 to-blue-500 hover:from-purple-700 hover:to-blue-600 transition-all"
                  >
                    Get Started <ArrowRight className="ml-2 h-4 w-4" />
                  </Button>
                </Link>
                <Link href="/login">
                  <Button
                    size="lg"
                    variant="outline"
                    className="w-full sm:w-auto border-purple-200 dark:border-purple-800 hover:bg-purple-500/10 hover:text-purple-500 transition-all"
                  >
                    Login
                  </Button>
                </Link>
              </div>
            </div>
          </div>
        </motion.section>
      </main>

      {/* Footer */}
      <footer className="border-t py-12 bg-background">
        <div className="container px-4 md:px-6">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div className="space-y-4">
              <Link
                href="/"
                className="font-bold text-2xl bg-clip-text text-transparent bg-gradient-to-r from-purple-600 to-blue-500"
              >
                Unifyze
              </Link>
              <p className="text-sm text-muted-foreground">
                Connecting university students for a better academic and social experience.
              </p>
              <div className="flex space-x-4">
                <a href="#" className="text-muted-foreground hover:text-purple-500 transition-colors">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="2"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    className="h-5 w-5"
                  >
                    <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path>
                  </svg>
                  <span className="sr-only">Facebook</span>
                </a>
                <a href="#" className="text-muted-foreground hover:text-purple-500 transition-colors">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="2"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    className="h-5 w-5"
                  >
                    <path d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z"></path>
                  </svg>
                  <span className="sr-only">Twitter</span>
                </a>
                <a href="#" className="text-muted-foreground hover:text-purple-500 transition-colors">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="2"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    className="h-5 w-5"
                  >
                    <rect width="20" height="20" x="2" y="2" rx="5" ry="5"></rect>
                    <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                    <line x1="17.5" x2="17.51" y1="6.5" y2="6.5"></line>
                  </svg>
                  <span className="sr-only">Instagram</span>
                </a>
              </div>
            </div>
            <div>
              <h3 className="font-medium mb-4">Product</h3>
              <ul className="space-y-2 text-sm">
                <li>
                  <a href="#" className="text-muted-foreground hover:text-purple-500 transition-colors">
                    Features
                  </a>
                </li>
                <li>
                  <a href="#" className="text-muted-foreground hover:text-purple-500 transition-colors">
                    Testimonials
                  </a>
                </li>
                <li>
                  <a href="#" className="text-muted-foreground hover:text-purple-500 transition-colors">
                    Pricing
                  </a>
                </li>
                <li>
                  <a href="#" className="text-muted-foreground hover:text-purple-500 transition-colors">
                    FAQ
                  </a>
                </li>
              </ul>
            </div>
            <div>
              <h3 className="font-medium mb-4">Company</h3>
              <ul className="space-y-2 text-sm">
                <li>
                  <a href="#" className="text-muted-foreground hover:text-purple-500 transition-colors">
                    About
                  </a>
                </li>
                <li>
                  <a href="#" className="text-muted-foreground hover:text-purple-500 transition-colors">
                    Blog
                  </a>
                </li>
                <li>
                  <a href="#" className="text-muted-foreground hover:text-purple-500 transition-colors">
                    Careers
                  </a>
                </li>
                <li>
                  <a href="#" className="text-muted-foreground hover:text-purple-500 transition-colors">
                    Contact
                  </a>
                </li>
              </ul>
            </div>
            <div>
              <h3 className="font-medium mb-4">Legal</h3>
              <ul className="space-y-2 text-sm">
                <li>
                  <a href="#" className="text-muted-foreground hover:text-purple-500 transition-colors">
                    Terms
                  </a>
                </li>
                <li>
                  <a href="#" className="text-muted-foreground hover:text-purple-500 transition-colors">
                    Privacy
                  </a>
                </li>
                <li>
                  <a href="#" className="text-muted-foreground hover:text-purple-500 transition-colors">
                    Cookies
                  </a>
                </li>
                <li>
                  <a href="#" className="text-muted-foreground hover:text-purple-500 transition-colors">
                    Licenses
                  </a>
                </li>
              </ul>
            </div>
          </div>
          <div className="mt-12 pt-8 border-t text-center text-sm text-muted-foreground">
            <p>© {new Date().getFullYear()} Unifyze. All rights reserved.</p>
          </div>
        </div>
      </footer>
    </div>
  )
}
