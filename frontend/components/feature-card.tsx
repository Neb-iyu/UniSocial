"use client"

import { motion } from "framer-motion"
import type { LucideIcon } from "lucide-react"

interface FeatureCardProps {
  icon: LucideIcon
  title: string
  description: string
  index: number
}

export function FeatureCard({ icon: Icon, title, description, index }: FeatureCardProps) {
  return (
    <motion.div
      initial={{ opacity: 0, y: 20 }}
      whileInView={{ opacity: 1, y: 0 }}
      transition={{ delay: index * 0.1 }}
      viewport={{ once: true }}
      whileHover={{
        scale: 1.03,
        boxShadow: "0 10px 30px rgba(0, 0, 0, 0.1)",
        transition: { duration: 0.2 },
      }}
      className="bg-background/80 backdrop-blur-sm border rounded-xl p-6 transition-all hover:shadow-purple-500/5 group"
    >
      <div className="mb-4 rounded-full bg-muted p-3 w-fit group-hover:bg-purple-100 dark:group-hover:bg-purple-900/30 transition-colors">
        <Icon className="h-10 w-10 text-purple-500 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors" />
      </div>
      <h3 className="text-xl font-bold mb-2 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">
        {title}
      </h3>
      <p className="text-muted-foreground">{description}</p>
    </motion.div>
  )
}
